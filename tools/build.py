"""
Builds data/wilayas.json, data/dairas.json, data/communes.json and data/postal_codes.json.

Sources (github.com/othmanus/algeria-cities):
  tools/source-algeria-cities.json     58 wilayas / 1541 communes
  tools/source-algeria-postcodes.json  post offices with their postal code, linked to commune ids

then applies the 2026 territorial division (law 26-06 of 4 April 2026, presidential decree 26-206 of
25 May 2026): 11 new wilayas (codes 59-69) carved out of existing ones, 69 wilayas in total.

Usage: python -I tools/build.py   (run from the package root)
"""
import difflib
import json
import re
import unicodedata
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "tools" / "source-algeria-cities.json"
SRC_POSTCODES = ROOT / "tools" / "source-algeria-postcodes.json"
OUT = ROOT / "data"

# Postal codes start with the wilaya number of the 1984 division (48 wilayas).
# Wilayas created in 2019 kept the prefix of the wilaya they came from.
POSTAL_PREFIX = {49: 1, 50: 1, 51: 7, 52: 8, 53: 11, 54: 11, 55: 30, 56: 33, 57: 39, 58: 47}

# Manual corrections to the source dataset (latin names).
LATIN_FIXES = {
    "Zmalet El Emir Abdelkade": "Zmalet El Emir Abdelkader",
}

# New wilayas: code -> (latin name, arabic name, mother wilaya code, dairas (latin, as in source) moved).
NEW_WILAYAS = {
    59: ("Aflou", "آفلو", 3, ["Aflou", "Gueltat Sidi Saad", "Brida", "El Ghicha", "Oued Morra"]),
    60: ("Barika", "بريكة", 5, ["Barika", "Djezzar", "Seggana"]),
    61: ("El Kantara", "القنطرة", 7, ["El Kantara", "Djemorah", "El Outaya"]),
    62: ("Bir El Ater", "بئر العاتر", 12, ["Bir El Ater", "Negrine"]),
    63: ("El Aricha", "العريشة", 13, ["Sidi Djillali", "El Aricha"]),
    64: ("Ksar Chellala", "قصر الشلالة", 14, ["Ksar Chellala", "Hamadia"]),
    65: ("Aïn Ouessara", "عين وسارة", 17, ["Ain Oussera", "Birine", "Had Sahary", "Sidi Laadjel"]),
    66: ("Messaad", "مسعد", 17, ["Messaad", "Faidh El Botma"]),
    67: ("Ksar El Boukhari", "قصر البخاري", 26,
         ["Ksar El Boukhari", "Ain Boucif", "Chellalat El Adhaoura", "Aziz", "Ouled Antar", "Chahbounia"]),
    68: ("Bou Saâda", "بوسعادة", 28,
         ["Bousaada", "Khoubana", "Ouled Sidi Brahim", "Sidi Ameur", "Ben Srour", "Ain El Melh",
          "Djebel Messaad", "Medjedel"]),
    69: ("El Abiodh Sidi Cheikh", "الأبيض سيدي الشيخ", 32, ["Labiodh Sidi Cheikh", "Boussemghoun", "Chellala"]),
}

# Dairas created by the split: El Aricha and El Gor leave Sebdou daira (Tlemcen) for wilaya 63.
SPLIT_DAIRAS = {
    # (mother wilaya, old daira latin) : (new daira latin, new daira arabic, [commune latin names moved])
    (13, "Sebdou"): ("El Aricha", "العريشة", ["El Aricha", "El Gor"]),
}

# Expected commune counts per new wilaya (law 26-06 annex as reported by the press).
EXPECTED_COMMUNES = {59: 12, 60: 8, 61: 5, 62: 4, 63: 4, 64: 6, 65: 10, 66: 8, 67: 21, 68: 23, 69: 7}


def clean(value: str, latin: bool = False) -> str:
    value = re.sub(r"\s+", " ", value).strip()
    if latin:
        value = value.replace("-", " ").strip()
        value = re.sub(r"\s+", " ", value)
        value = LATIN_FIXES.get(value, value)
    return value


def latin_key(value: str) -> str:
    value = unicodedata.normalize("NFKD", value)
    value = "".join(ch for ch in value if not unicodedata.combining(ch))
    return re.sub(r"[^a-z0-9]", "", value.lower())


def arabic_key(value: str) -> str:
    value = value.split(" - ")[0]  # "أدرار - القبّاضة الرّئيسيّة" -> "أدرار"
    value = re.sub(r"[ً-ْـ]", "", value)
    value = value.translate(str.maketrans("أإآةى", "اااهي"))
    return re.sub(r"\s+", "", value)


def similar(a: str, b: str) -> bool:
    return bool(a and b) and difflib.SequenceMatcher(None, a, b).ratio() >= 0.8


def postal_codes(source: dict) -> tuple[dict, list]:
    """
    Returns ({commune id: main postal code}, [every post office postal code]).

    The main postal code is the commune's own post office (same name), else the closest name,
    else the lowest code among the post offices located in the commune.
    """
    prefix = lambda wilaya_code: POSTAL_PREFIX.get(int(wilaya_code), int(wilaya_code))

    # (postal prefix, commune name) -> commune ids, to repair offices the source attached to a
    # homonymous commune of another wilaya (e.g. "Sidi Ameur" exists in M'Sila and El Bayadh).
    homonyms = {}
    for commune_id, r in source.items():
        homonyms.setdefault((prefix(r["wilaya_code"]), latin_key(r["commune_name_ascii"])), []).append(commune_id)

    offices = []
    for o in json.loads(SRC_POSTCODES.read_text(encoding="utf-8")):
        code, commune_id = o["post_code"].strip(), o["commune_id"]
        if not re.fullmatch(r"\d{5}", code) or commune_id not in source:
            continue
        if int(code[:2]) != prefix(o["wilaya_code"]):
            match = homonyms.get((int(code[:2]), latin_key(source[commune_id]["commune_name_ascii"])), [])
            if len(match) != 1:
                continue  # cannot tell which commune this office belongs to
            commune_id = match[0]
        offices.append({
            "code": code,
            "commune_id": commune_id,
            "name": clean(o["post_name_ascii"]),
            "name_ar": clean(o["post_name"]),
        })

    by_commune = {}
    for o in offices:
        by_commune.setdefault(o["commune_id"], []).append(o)

    main = {}
    for commune_id, candidates in by_commune.items():
        cl = latin_key(source[commune_id]["commune_name_ascii"])
        ca = arabic_key(source[commune_id]["commune_name"])

        def rank(o):
            ol, oa = latin_key(o["name"]), arabic_key(o["name_ar"])
            if o["code"].endswith("000"):
                return 0  # main office of the wilaya, located in its chef-lieu
            if ol == cl or oa == ca:
                return 1
            if similar(ol, cl) or similar(oa, ca):
                return 2
            if len(cl) >= 4 and (cl in ol or (len(ca) >= 4 and ca in oa)):
                return 3
            return 4

        main[commune_id] = min(candidates, key=lambda o: (rank(o), o["code"]))["code"]

    return main, sorted(offices, key=lambda o: o["code"])


def main() -> None:
    rows = json.loads(SRC.read_text(encoding="utf-8"))
    rows.sort(key=lambda r: (int(r["wilaya_code"]), r["id"]))

    # --- legacy (58) wilayas
    wilayas = {}
    for r in rows:
        code = int(r["wilaya_code"])
        wilayas.setdefault(code, {
            "id": code,
            "code": f"{code:02d}",
            "name": clean(r["wilaya_name_ascii"], latin=True),
            "name_ar": clean(r["wilaya_name"]),
            "parent_id": None,
        })
    assert len(wilayas) == 58, len(wilayas)

    for code, (name, name_ar, mother, _) in NEW_WILAYAS.items():
        wilayas[code] = {"id": code, "code": f"{code:02d}", "name": name, "name_ar": name_ar, "parent_id": mother}

    # --- legacy dairas, ordered by wilaya then first commune id
    dairas = {}
    for r in rows:
        key = (int(r["wilaya_code"]), clean(r["daira_name_ascii"], latin=True))
        if key not in dairas:
            dairas[key] = {
                "id": len(dairas) + 1,
                "wilaya_id": key[0],
                "legacy_wilaya_id": key[0],
                "name": key[1],
                "name_ar": clean(r["daira_name"]),
            }
    assert len(dairas) == 548, len(dairas)

    # dairas that only exist in the 2026 division
    split_new = {}
    for (mother, old), (name, name_ar, communes) in SPLIT_DAIRAS.items():
        d = {"id": len(dairas) + len(split_new) + 1, "wilaya_id": mother, "legacy_wilaya_id": None,
             "name": name, "name_ar": name_ar}
        split_new[(mother, old)] = (d, set(communes))

    # move dairas to their new wilaya
    all_dairas = {**dairas, **{(d["wilaya_id"], d["name"]): d for d, _ in split_new.values()}}
    for code, (_, _, mother, moved) in NEW_WILAYAS.items():
        for daira_name in moved:
            key = (mother, daira_name)
            assert key in all_dairas, f"daira {daira_name} not found in wilaya {mother}"
            all_dairas[key]["wilaya_id"] = code

    # --- communes
    main_postal_codes, offices = postal_codes({r["id"]: r for r in rows})
    communes = []
    for r in rows:
        legacy_wilaya = int(r["wilaya_code"])
        legacy_daira = dairas[(legacy_wilaya, clean(r["daira_name_ascii"], latin=True))]
        name = clean(r["commune_name_ascii"], latin=True)
        daira = legacy_daira
        split = split_new.get((legacy_wilaya, legacy_daira["name"]))
        if split and name in split[1]:
            daira = split[0]
        communes.append({
            "id": r["id"],
            "daira_id": daira["id"],
            "wilaya_id": daira["wilaya_id"],
            "legacy_daira_id": legacy_daira["id"],
            "legacy_wilaya_id": legacy_wilaya,
            "name": name,
            "name_ar": clean(r["commune_name"]),
            "postal_code": main_postal_codes.get(r["id"]),
        })
    communes.sort(key=lambda c: c["id"])

    # --- sanity checks
    assert len(communes) == 1541
    assert len({c["id"] for c in communes}) == 1541
    for code, expected in EXPECTED_COMMUNES.items():
        got = sum(1 for c in communes if c["wilaya_id"] == code)
        assert got == expected, f"wilaya {code}: {got} communes, expected {expected}"
    for (mother, old), (d, names) in split_new.items():
        assert sum(1 for c in communes if c["daira_id"] == d["id"]) == len(names)
    used = {c["daira_id"] for c in communes}
    assert used == {d["id"] for d in all_dairas.values()}, "every daira must have communes"
    assert len({c["wilaya_id"] for c in communes}) == 69
    assert len({o["code"] for o in offices}) == len(offices), "postal codes must be unique"

    OUT.mkdir(exist_ok=True)
    dump = lambda name, data: (OUT / name).write_text(
        "[\n" + ",\n".join(json.dumps(x, ensure_ascii=False) for x in data) + "\n]\n", encoding="utf-8")
    dump("wilayas.json", sorted(wilayas.values(), key=lambda w: w["id"]))
    dump("dairas.json", sorted(all_dairas.values(), key=lambda d: d["id"]))
    dump("communes.json", communes)
    dump("postal_codes.json", offices)
    with_code = sum(1 for c in communes if c["postal_code"])
    print(f"wilayas={len(wilayas)} dairas={len(all_dairas)} communes={len(communes)} "
          f"postal_codes={len(offices)} communes_with_postal_code={with_code}")


if __name__ == "__main__":
    main()
