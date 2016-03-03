
## User
* [x] Multiple two step/two factor authentication modes allow you to choose the most suitable for yourself.
* [x] For each authentication method there are several options, which can be set by the user.
* [x] Users can use different Threema IDs for each two step verification method.

## Administrative
* [x] You can use both the basic mode and the end-to-end mode. (although the latter is recommend and provides more features)
* [x] You can see the Gateway status in the ACP at a glance.
* [x] You can see your remaining credits in the ACP at a glance.
* [x] You can use the Gateway completely without offering two factor authentication (2FA) or limit the 2FA methods.

## Security

* [x] Sensitive settings are hidden in the ACP.
* [x] You can set permissions which users can send or receive messages with the Threema Gateway.
* [x] You can hardcode your private key and other details into the PHP file instead of using the XenForo settings, which store the secrets in your database.
* [x] You can generate the private key on the server and leave it there by just specifying the file where it is saved.
* [x] Permissions allow you to control every aspect of your Gateway.
* [x] The only external calls this add-on makes are the ones to Threema.
* [x] By default the add-on uses the [advanced settings](https://github.com/rugk/threema-msgapi-sdk-php#user-content-creating-a-connection-with-advanced-options) of the PHP SDK, which provide better HTTPS security when sending messages
