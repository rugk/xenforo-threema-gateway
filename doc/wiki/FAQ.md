# Frequently asked questions

## General

### I'd like to use the 2FA with Threema for my own forum, but I don't use XenForo. Will there be any ports for other forum software?
Currently there are no plans to do so. However it is certainly possible and any developer can do so as the PHP SDK and this add-on are both open-source.

Basically it would be very good to have already some support for two-factor-authentication implemented in the forum software. (see next two questions)

### Why did you choose XenForo?

The main reason was that XenForo v1.5 added two-factor-authentication as native feature of XenForo and allowed it to be expanded by add-ons. Many users requested SMS 2FA.
However SMS is insecure (see "[Why not just SMS?](#why-not-just-sms)") and the Threema Gateway is just perfectly suitable for such a task as sending one time passwords to users.

### Why is the add-on only compatible with XenForo 1.5?
The reason is that XenForo 1.5 introduced two-factor-authentication and the 2FA modews of this add-on work as a 2FA provider internally. As the add-on depends on this feature, it does not work with earlier versions of XenForo.

### Why did you choose Threema? I'd like to have WhatsApp instead!
This has several reasons:
1. WhatsApp is not privacy aware.
2. WhatsApp does not yet have a easy-to-use, secure API which allow sending or receiving of such messages.

The advantages of Threema (Gateway) are:
1. The messages can be send end-to-end encrypted.
2. It is cheap. (cheaper than SMS)
3. The messages are all send through servers in Switzerland.
4. Threema allows you to accept/decline messages, which is used in the "Fast login" method

More advantages of the Threema Gateway can be found [on the official website](https://gateway.threema.ch).

### And why no other messenger such as Telegram or Signal?
These messengers also have no easy and secure API for sending messages to users.

### Why not just SMS?
SMS is [insecure](https://stackoverflow.com/questions/1374979/mobile-programming-how-secure-is-sms), [especially for 2FA](https://www.fredericjacobs.com/blog/2016/01/14/sms-login/). Even the NIST [advocates not to use SMS for 2FA](https://www.iansresearch.com/insights/blog/blog-insights/2016/07/28/sending-out-sms-nist-recommends-shifting-to-alternative-2fa-methods). The Threema Gateway is just perfectly suitable for such tasks as sending OTP (one time passwords) to users.
Additionally sending messages with the Threema Gateway is cheaper than sending SMS messages and it works internationally without any problems.

### But the code generation method via app is more secure, isn't it?
Yes, it is as it works completely offline. That's also the reason why it is showed above the Threema 2FA methods when the user goes to the 2FA settings.

However 2FA verification with Threema is nearly as secure as the [TOTP](https://en.wikipedia.org/wiki/Time-based_One-time_Password_Algorithm) verification (that's how the app verification is called), but it may be much more convenient and the user can choose from several 2FA methods.
Additionally it is always good to have a second 2FA method activated as a backup.

## Using Two-factor-authentication

### Which 2FA mode should I choose?
There are three possible 2FA modes. Whether they are available depends on your server admin.

Generally all modes which are listed first in the 2FA mode overview are more secure and these which are listed last are less secure. In general the ones listed below however provide more convenience.

You can jsut try them out or read [the detailed and somewhat technical description of the different modes](../doc/2faMethods.md) to get more details of how they work and what advantages and disadvantages they have.

### What happens if I lose my Threema ID as a 2FA user?
If you lose access to your Threema ID you cannot use the 2FA method anymore, which used this ID. You may use a backup code of XenForo or another 2FA method if set one up.

Remember you can choose a different Threema ID for each 2FA mode. Thus you can use one Threema ID even when you loose access to another one.

Always remember to [create a backup](https://threema.ch/en/faq/id_backup_expl) of your Threema ID to prevent such issues. If you have a backup you can just restore it and receive and send messages again.

### Can I see whether end-to-end-encryption is used for my messages?

Yes. At first if the modes "reversed" or "fast login" are available, which require the server admin to receive messages, you can be sure taht end-to-end encryption is used as this is a requirement by Threema.

If only the "conventional" mode is available, you can see it in the description of all available modes as the last sentence clearly states when E2E encryption is used:
> The message is sent end-to-end encrypted.

If this sentence is not shown there, no end-to-end encryption is used.

## Setup and Troubleshooting

### I have installed the add-on. What to do now? How can I set it up?

See [Setup](../doc/setup.md).

### How can I setup a PHP keystore?

At first note that this is not needed as the database is used by default to store the public keys of Threema IDs.
However when you think you want to use a PHP keystore follow this steps:

1. Create a php file in `library/ThreemaGateway`, e.g. `library/ThreemaGateway/mykeystore.php`.
2. Make sure it is writable. If not e.g. use `chmod` to make it writable. You will get an error later if it is not writable.  
    Also make sure to make it "read-only" for other users on the system, so only the process running PHGP with XenForo can read (& write) to it.
3. Go to the Threema Gateway settings in the ACP and select "Use PHP Keystore (not recommend)".
4. Enter the filename (and extension) of your PHP keystore, e.g. `mykeystore.php`.
5. Save the changes.

### How can I hardcode my credentials into the PHP file?

When you do so, please be aware that every update of this addon overwrites these changes and therefore you have to do them again when updating this add-on.

1. Open the file `library/ThreemaGateway/Handler/Settings.php`. There you see the following code snippet:

   ```php
   /**
   * @var string $GatewayId Your own Threema Gateway ID
   */
   private $GatewayId = '';

   /**
   * @var string $GatewaySecret Your own Threema Gateway Secret
   */
   private $GatewaySecret = '';

   /**
   * @var string $PrivateKey Your own private key
   */
   private $PrivateKey = '';
   ```

2. Replace the values in such a way that they e.g. look like this:

   ```php
   /**
   * @var string $GatewayId Your own Threema Gateway ID
   */
   private $GatewayId = '*MYAPIID';

   /**
   * @var string $GatewaySecret Your own Threema Gateway Secret
   */
   private $GatewaySecret = 'ab2defghijKlmnOp';

   /**
   * @var string $PrivateKey Your own private key
   */
   private $PrivateKey = 'private:94af3260fa2a19adc8e82e82be598be15bc6ad6f47c8ee303cb185ef860e16d2';
   ```

3. Now you can remove all data in the ACP options (just set them to a blank field) and if everything is correct you will still see your remaining credits. If not, there will be an error.

Note that the health check displays an error for the edited file afterwards. If you do not want this you can calculate the checksum of the changed file and replace it in `library\ThreemaGateway\Listener\FileHealthCheck.php`.

### Can I get this add-on to use the Threema IDs from user fields?

Yes, you can! Your custom user field just have to has the ID `threemaid` and this add-on will automatically use it. In the 2FA methods it is however only entered as the default value and users can override it if they want.
The best thing is: This user field will be added automatically when installing this add-on so you do not even have to set it up manually.

Also mail addresses will be automatically looked up if the corresponding option is activated in the settings.

### How can I setup the option to lookup the phone number automatically?
1. Create a custom user field where your users can put their phone number in. You may configure this to be publicly visible or not.
  ![add phone user field 01](AddPhoneUserField01.PNG)

2. The new user field gets added:
  ![add phone user field 03](AddPhoneUserField03.PNG)

3. Go to the 2FA settings, activate the phone lookup and enter the field ID you choose earlier there:
  ![add phone user field 04](AddPhoneUserField04.PNG)

Note that your users have to include the [country calling codes](https://en.wikipedia.org/wiki/List_of_country_calling_codes) in the phone number. So e.g. they have to write "+41 791 234567" instead of "0791 234567". However spaces and the "+" sign at the beginning are not important.  
If it does not work also make sure that the users have the "lookup" permission.

### Messages are not send or received. What should I do?

You should make sure you [setup](../doc/setup.md) everything correctly. Visit the ACP options page and look at the status. There you will eventually see error messages. Also make sure you have enough credits to send messages.

If this does not help, consider opening an issue.

## 2FA Setup & Troubleshooting

### It does not display me the Threema 2FA methods. What's happening?

This add-on has multiple ways which deactivate one or more 2FA methods. Please make sure that...

1. Your server setup is complete (look at the status message in the ACP)
2. Your credentials are correct
3. The user group has enough permissions to use the specific 2FA method. E.g. for the conventional mode the users must have permission to use the Gateway, send messages, fetch public keys (lookup) and use the 2FA mode.
4. The 2FA method is activated in the settings.

### How can I hide a 2FA method?

There are multiple way to do this. You can hide it...

- for all users by disabling it in the settings
- for some user groups by removing the permission to use the Gateway. Note that this mostly affects multiple 2FA modes.

### I deactivated a 2FA mode, but users still use this mode to login. How is that possible?

When you disable a 2FA mode this only prevents users from activating this mode. Users, who had this mode activated before, do not notice any difference and can still login with this mode. This makes sure that no users get locked out.

If you want to prevent users from using the 2FA at all, you can limit the permissions for the user/user group, so that they cannot use the Threema Gateway or the 2FA of the Threema Gateway anymore. However be **careful when doing so** as this may cause serious problems, because when users do not use any other 2FA method, they will not only get errors, but will also have no way to login anymore. So be careful when disabling 2FA modes!

## Customisation

### How do I add smilies to my messages?
All messages this add-on sends are saved as phrases. Unicode emotions cannot be saved in phrases directly.
However you can use Unicode characters in the format `\u<hexnum>`. You can use the same format as in C, C++ or Java source code (where UTF-16 is used). There are also [converters](https://www.branah.com/unicode-converter) for this task.
