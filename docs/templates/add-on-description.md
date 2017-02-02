When the [two-step authentication was introduced into XenForo](https://xenforo.com/community/threads/two-step-verification-and-security-improvements.99881/) many server admins asked for **SMS support**. This was my inducement to create this add-on. But instead of using SMS - which is not suitable as [it is insecure](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#why-not-just-sms) - I thought one can also just use a well-known, secure instant messaging app.

---

This add-on integrates the secure instant-messaging app [Threema](https://threema.ch) into the forum software XenForo. Using the [Threema Gateway](https://gateway.threema.ch) it offers three new [two-step authentication](https://xenforo.com/community/threads/two-step-verification-and-security-improvements.99881/) (also called two-factor authentication or "2FA") modes for users and admins.

Source code: <https://github.com/rugk/xenforo-threema-gateway>  
FAQ: <https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ>  
XenForo: [coming soon…]  
Download: <https://github.com/rugk/xenforo-threema-gateway/releases/latest>  

**[Three 2FA modes](https://github.com/rugk/xenforo-threema-gateway/blob/master/docs/2faMethods.md) offer you and your users a flexible and secure way for easily securing their account.**  
Multiple settings allow server admins to configure and/or restrict any aspect of the add-on. Additionally one can always extend this add-on to do other things with the Threema Gateway.  
The add-on is built with security, flexibility and user choice in mind and is straightforward to use.  
If you are a XenForo admin, who wants to increase the security of your forum then you should use this add-on. It allows users to secure their login with a solid, well-tested, famous crypto-messenger app as an alternative to the existing 2FA options in XenForo. When you enable all the three completely new Threema 2FA modes, forum members have a diverse choice and can do anything from not enabling a single mode to enabling all of them.  
So let's allow users to keep their accounts under their control and make the world a little safer!

---

### Top features

* **Choice:** Let users and forum admins choose from up to **three 2FA algorithms** with two of them using a **new concept**.
* **Adaptability:** You can configure nearly **anything in the ACP** to tailor the add-on to your requirements.
* **Flexibility:** Forum members can **independently configure each one** of the three 2FA modes up to satisfy their personal needs.
* **Free and secure:** Being **open-source** (and [free software](https://en.wikipedia.org/wiki/Free_software)) it's reviewable by any independend researcher to **easily verify** the security at any time.
* **Extensibility:** By providing an API any other add-on can add **even more features** to your add-on.

#### More features

<!-- admin -->
* Works with the **basic mode** or - as recommend - the **end-to-end mode** of the [Threema Gateway](https://gateway.threema.ch/en/products).
* Easy display of Threema **Gateway status** & remaining credits directly in ACP.
* Integrated guide and [Wiki]((https://github.com/rugk/xenforo-threema-gateway/wiki/Setup) help you with the **server setup**.
<!-- security -->
* Automatic display of Threema ID verification **QR codes**.
* Your **private key** stays on your server!
* Detailed **permissions** to control the whole add-on
* Integration into XenForo's **health check**
* **No calls** to any third-parties servers, only calls to Threema's Gateway API!
* **Security settings** are improvable with a few clicks in the ACP.
<!-- more -->
* **Easy to use** for any Threema user.

---

**Featured FAQs:**
* [What is 2FA and why should I use 2FA?](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#what-is-two-factor-authentication-and-why-should-i-use-it)
* [Why should not I use SMS for 2FA?](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#why-not-just-sms)
* [Why use Threema?](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#why-did-you-choose-threema-id-like-to-have-whatsapp-instead)

---

You will get access to this release and future ones when you donate **15€** with the "donwload/buy" button at the top.
Of course, as this is an open-source project, this is not necessary and you can just build it by yourself, but each donation helps to keep this project alive. After all this are just five bucks per 2FA method. More information can be found in the [wiki](https://github.com/rugk/xenforo-threema-gateway/wiki/FAQ#donations--releases).  
**Note:** I reserve my right to require another donation for mayor releases (from version `1.x.y` to `2.x.y`) if basic code changes have been done.
