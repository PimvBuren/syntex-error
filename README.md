# Secure File Transfer System

## Projectbeschrijving
Dit project heeft als doel een veilig bestandstransfersysteem te ontwikkelen waarmee bestanden veilig kunnen worden verzonden tussen systemen. Het systeem richt zich op vertrouwelijkheid (confidentiality), integriteit (integrity) en authenticatie (authentication).

---

## Probleemanalyse

### Gebruikers
- Systeembeheerders
- Medewerkers
- Externe systemen

### Soorten bestanden
- PDF-documenten
- Afbeeldingen
- Software builds
- Gevoelige bedrijfsgegevens

### Beveiligingsrisico's
- Onderschepping van bestanden
- Manipulatie van bestanden
- Ongeautoriseerde toegang
- Verlies van gegevens

### Aanvallen die voorkomen moeten worden
- Man-in-the-Middle aanvallen
- Ongeautoriseerde downloads
- Wijziging van bestanden tijdens transport

---

## Security Requirements

- Encryptie van bestanden tijdens transport via HTTPS/TLS
- Authenticatie met gebruikersnaam en wachtwoord
- Integriteitscontrole via SHA-256 hashing
- Logging van uploads en downloads
- Foutafhandeling bij mislukte overdrachten


### Componenten
- Client (HTML, CSS, JavaScript)
- Server (PHP)
- Bestandsopslag
- Logging systeem


## Technische Keuzes

| Onderdeel | Technologie |
|------------|-------------|
| Backend | PHP |
| Frontend | HTML, CSS, JavaScript |
| Protocol | HTTPS |
| Encryptie | TLS 1.3 |
| Integriteitscontrole | SHA-256 |
| Opslag | Serverbestandssysteem |
| Logging | PHP Logbestanden |

---


### Backlog
- README schrijven
- Probleemanalyse maken
- Security requirements opstellen
- Architectuurdiagram ontwerpen
- Technische keuzes documenteren

### To Do
- Git repository opzetten
- README afronden

### Doing
- Architectuur ontwerpen

### Done
- Projectidee vastgesteld
