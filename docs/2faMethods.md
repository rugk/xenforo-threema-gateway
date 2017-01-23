# Two factor authentication methods
## Overview
Basic priority: 40 (see `ThreemaGateway_Constants::TFA_BASE_PRIORITY`)  
Range reserved: `TFA_BASE_PRIORITY-29` to `TFA_BASE_PRIORITY-1` (currently 21-39)

name         | server                                       | client                                     | user                                             | implemented (class)             | priority
------------ | -------------------------------------------- | ------------------------------------------ | ------------------------------------------------ | ------------------------------- | --------
Conventional | send 6 digits code                           | receive code                               | enter code into website                          | ThreemaGateway_Tfa_Conventional | 25
Reversed     | receives 6 digits code                       | sends 6 digits code                        | types code from website into phone/scans QR code | ThreemaGateway_Tfa_Reversed     | 30
Fast login   | sends login message, receives status updates | receives message, sends status update back | taps on accept/decline on their phone            | ThreemaGateway_Tfa_Fast         | 35

## Conventional

**Pro:** Well-tested standard method  
**Contra:** Inconvenient to use

The user gets a 6 digit code from the server delivered via Threema server and enters it into the website.  
This is like SMS verification, but it uses Threema and is therefore much more secure.

![conventional sketch: server sends code, client receives it](https://cdn.rawgit.com/rugk/xenforo-threema-gateway/master/docs/images/Conventional.svg)

This security model is well-tested and proven to work when the secret is transmitted over secure channels and only the receipient can read it. This is satisfied by confidentiality implied by the end-to-end-encryption used in Threema.
Thus if no end-to-end-encryption is used the security level is weakend very much and therefore a small message is shown to the 2FA user when E2E encryption is used, so one can differenciate this even as a user of the 2FA mode.

[![preview of 2FA conventional mode](https://cdn.rawgit.com/rugk/xenforo-threema-gateway/8802cf95/docs/screenshots/conventionalLoginDesktopPlaySmall.png)](https://cdn.rawgit.com/rugk/xenforo-threema-gateway/8802cf95/docs/screencasts/conventionalLoginDesktop.webm)

## Reversed
**Pro:** Security depends on unique token  
**Contra:** Still quite inconvenient as one has to handle the token

The user gets a 6 digit code from the server delivered directly on the login site. The users sends this code via Threema to the server (to the Gateway ID).  
This is as secure as the first methods, but it may be more convenient for the user as they can use multiple methods for sending the code via Threema instead of having to transcribe it manually.

![reversed sketch: server sends code to user, client sends it to server via Threema](https://cdn.rawgit.com/rugk/xenforo-threema-gateway/master/docs/images/Reversed.svg)

The random code ('token') must not be taken as a secret here as the authentication is mostly done only by ensuring that the (previously registered) Threema ID sended a message stating to allow the login. The NaCl encryption ensures authentity of a message and the secret should just be unique to prevent potential replay attacks.

[![preview of 2FA reversed mode](https://cdn.rawgit.com/rugk/xenforo-threema-gateway/8802cf95/docs/screenshots/reversedLoginDesktopPlaySmall.png)](https://cdn.rawgit.com/rugk/xenforo-threema-gateway/8802cf95/docs/screencasts/reversedLoginDesktop.webm)

## Fast login
**Pro:** Very convenient, fast to use and allows 'banning' of unauthorized login requests  
**Contra:** Security depends on user (message ID as invisible 'secret')

The user gets a message telling them a user requested to login. They can now accept or decline the message. If the message is accepted the user is granted access.  
This is the most convenient mode for the user, but it does not depend on any visible code anymore. Similar methods are used for Twitter and Microsoft's 2FA, but they use their own apps for this.

![fast login sketch: server sends message to user, user acknowledges or declines it](https://cdn.rawgit.com/rugk/xenforo-threema-gateway/master/docs/images/FastLogin.svg)

This is secure, because here the message ID acts as a secret only known to the server and the client. This secret is never transfered outside of the Threema network or the forum sever itself. Nevertheless the security of the system does not depend on this confidentially of this "secret", i.e. it does not hurt if the secret becomes known publicy. Mostly it only prevents replay attacks.  
By acknowledging a message the client creates an authenticated and end-to-end-encrypted message stating that the previously received message ID (and therefore the message) has been acknowledged. As the message asks the question whether to allow login this is a cryptographic proof of the user's decicion and can therefore be validated by the server.

The security mostly depends on how the user can estimate the vadility of the login request (which includes the IP address). An attacker could try to login at the same time and trick the user into acknowledging the wrong message.

As an extra with this method users can explicitly state that the login access was unwanted (by declining a message). This allows one to execute different actions when this happens, such as banning the IP/user, who tried to login.

[![preview of 2FA fast mode](https://cdn.rawgit.com/rugk/xenforo-threema-gateway/8802cf95/docs/screenshots/fastLoginDesktopPlaySmall.png)](https://cdn.rawgit.com/rugk/xenforo-threema-gateway/8802cf95/docs/screencasts/fastLoginDesktop.webm)
