Als die [Zwei-Schritt-Authentifizierung in XenForo eingeführt wurde](https://xenforo.com/community/threads/two-step-verification-and-security-improvements.99881/) hatten viele Server-Admins nach **SMS-Unterstützung** gefragt. Dies war mein Anlass dieses Add-on zu erstellen. Aber anstatt SMS zu benutzen - welche nicht geeignet ist, da sie [unsicher ist](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#why-not-just-sms) - dachte ich, man kann auch einfach einen bekannten, sicheren Instant Messenger nutzen.

---

Dieses Add-on integriert die sichere Instant-Messaging-App  [Threema](https://threema.ch) in die Forensoftware XenForo. Durch Benutzung des [Threema Gateways](https://gateway.threema.ch) bietet es drei neue Arten der  [Zwei-Schritt-Authentifizierung](https://xenforo.com/community/threads/two-step-verification-and-security-improvements.99881/) (auch Zwei-Faktor-Authentifzierung, "2FA", genannt) für Nutzer und Admins an.

Quellcode: <https://github.com/rugk/xenforo-threema-gateway>  
Meistgestellte Fragen (FAQ): <https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ>  
XenForo: [coming soon…]  
Download: <https://github.com/rugk/xenforo-threema-gateway/releases/latest>  

**[Drei 2FA-Methoden](https://github.com/rugk/xenforo-threema-gateway/blob/master/docs/2faMethods.md) bieten dir und deinen Nutzern eine flexible und sichere Möglichkeit den Account abzusichern.**  
Mehrere Einstellungen ermöglichen es Server-Admins jeden Aspekt des Add-ons anzupassen oder zu beschränken. Zusätzlich kann dieses Add-on jederzeit erweitert werden, um andere Dinge mit dem Threema Gateway auszuführen.  
Dieses Add-on wurde mit den Zielen Sicherheit, Flexibilität und Benutzerfreundlichkeit erstellt.  
Wenn du ein XenForo-Admin bist, der die Sicherheit seines Forums verbessern will, dann solltest du dieses Add-on nutzen. Es ermöglicht Nutzern, ihren Login mit einem soliden, gut-getesteten, bekannten Krypto-Messenger als Alternative zu den bereits eingebauten 2FA-Optionen in XenForo abzusichern. Wenn du alle 3 komplett neuen Modi aktivierst, haben die Forenmitglieder eine große Auswahl und können alles - von der Nutzung keines der Methoden bis zum Aktivieren aller Methoden - tun.  
Also biete Nutzern mit diesem Add-on die Möglichkeit, ihre Accounts unter Kontrolle zu halten und die Welt ein kleines bisschen sicherer zu machen!

---

### Top-Features

* **Wahl:** Lasse Nutzer und Forenadmins von bis zu **drei 2FA-Algorithmen** wählen, wobei zwei davon ein komplett **neues Konzept** nutzen.
* **Anpassbarkeit:** Du kannst **fast alles im ACP** konfigurieren und auf deine Anforderungen zuschneiden.
* **Flexbilität:** Forummitglieder können die 2 2FA-Modi **unabhängig voneinander konfigurieren** um ihren persönlichen Anforderungen gerecht zu werden.
* **Frei und sicher:** Als **Open-source** (und [freie Software](https://de.wikipedia.org/wiki/Freie_Software)) kann das Add-on jederzeit von unabhängigen Forschern analysiert werden und auf einfache  Weise die **Sicherheit verifiziert** werden.
* **Erweiterbarkeit:** Durch Bereitstellen einer API kann jedes andere Add-on dieses Add-on erweitern und so **noch mehr Features** hinzufügen.

#### Mehr Features

<!-- admin -->
* Funktionsfähig mit dem **Basic-Modus** oder - wie empfohlen - dem **Ende-zu-Ende-verschlüsselten Modus** des [Threema Gateways](https://gateway.threema.ch/de/products)
* Einfache Anzeige des **Gateway-Status** und der verbleibenden Credits direkt im ACP.
* Ein integrierter Guide und das  [Wiki](https://github.com/rugk/xenforo-threema-gateway/wiki/Setup) helfen beim **Einrichten des Add-ons**.
<!-- security -->
* Automatische Anzeige des Threema-ID-Verifikations-**QR-Codes**.
* Dein **privater Schlüssel** verlässt deinen Server nie!
* Detaillierte **Berechtigungen** zum Kontrollieren des gesamten Add-ons
* Integration in Xenforos **Dateiintegritätsprüfung**
* **Keine Anfragen** an irgendeinen Drittparteiserver, nur zur den Threema-Gateway-Servern!
* Die **Sicherheitseinstellungen** sind mit ein paar Klicks im ACP verbesserbar.
<!-- more -->
* **Einfach** für jeden Threema-Nutzer zu benutzen.

---

**Vorgeschlagene FAQs (englisch):**
* [Was ist 2FA und warum sollte ich es nutzen?](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#what-is-two-factor-authentication-and-why-should-i-use-it)
* [Warum sollte ich nicht SMS für 2FA nutzen?](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#why-not-just-sms)
* [Warum Threema nutzen?](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#why-did-you-choose-threema-id-like-to-have-whatsapp-instead)

---

Du wirst Zugriff zu diesem und zukünftigen Releases bekommen, wenn du **15€** über den "Download/Kaufen"-Button oben spendest.
Natürlich ist dies nicht notwendig, da du es dir auch einfach selbst builden kannst, aber jede Spende hilft dieses Projekt am Leben zu erhalten. Außerdem sind dies nur 5€ pro 2FA-Methode. Mehr Informationen können im [Wiki (englisch)](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#donations--releases) gefunden werden.  
**Hinweis:** Ich behalte mir vor, für Mayor-Releases (also von Version `1.x.y` zu `2.x.y`) eventuell erneut eine Spende zu verlangen, falls grundlegende Veränderungen in diesen Versionen auftreten.
