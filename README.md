
## User
* Multiple two step/two factor authentication modes allow you to choose the most suitable for yourself.
* For each authentication method there are several options, which can be set by the user.
* Users can use different Threema IDs for each two step verification method.

## Administrative
* You can use both the basic mode and the end-to-end mode. (although the latter is recommend and provides more features)
* You can see the Gateway status in the ACP at a glance.
* You can see your remaining credits in the ACP at a glance.
* You can use the Gateway completely without offering two factor authentication (2FA) or limit the 2FA methods.
* The ACP helps you with the whole setup. Beginning with the installation of Libsodium until creating your private key and configuring the add-on's settings.

## Developer
* This add-on can easily be extended as it provides an API you can use to do your own things with the Threema Gateway.
* You can find extensive instructions in the [`doc`](/doc/) dir.

## Security

* Sensitive settings are hidden in the ACP.
* You can set permissions which users can send or receive messages with the Threema Gateway.
* You can hardcode your private key and other details into the PHP file instead of using the XenForo settings, which stores the secrets in your database.
* You can generate the private key on the server and leave it there (outside of the web root) by just specifying the file where it is saved.
* Permissions allow you to control every aspect of your Gateway.
* The only external calls this add-on makes are the ones to Threema.
* By default the add-on uses the [advanced settings](https://github.com/rugk/threema-msgapi-sdk-php#user-content-creating-a-connection-with-advanced-options) of the PHP SDK, which provide better HTTPS security when sending messages.
