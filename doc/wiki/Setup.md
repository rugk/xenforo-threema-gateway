# How to setup this add-on

## 1. Installation

1. Install this add-on.
2. Go to the ACP setting "Threema Gateway". It is very likely that you see at least one error message at the top.  
3. These error messages should vanish when you are finished with the setup. You can reload the site at any time and see what is still missing.  
4. Note that at this point of time no 2FA method is activated and your users should not notice any difference.\*
5. At this point you [have to decide](https://gateway.threema.ch/en/products) whether you want to use the basic mode or the end-to-end mode of the Threema Gateway. The latter is recommend as it not only provides more security, but it also allows XenForo to receive messages, which is required by some 2FA modes.  
6. When you want to use the basic mode create this ID on <https://gateway.threema.ch> and continue with step 3.  
7. When you want to use the end-to-end encrypted mode continue with the next step.
8. You should now install libsodium. How to do this is e.g. described [in step one](https://github.com/rugk/threema-msgapi-sdk-php/wiki/How-to-generate-a-new-key-pair-and-send-a-message#1-preparation) of the "generate a new key pair" guide.

\* It has to be said that your users could already see a minor difference: The custom user field for their Threema ID is already added. But this should not cause any inconveniences as by default only the format of the input is checked.

## 2. Threema Gateway setup

1. If you installed everything correctly you should already see no error messages in the ACP anymore. Now you need to generate your private key.
2. On your server navigate to `library/ThreemaGateway/threema-msgapi-sdk-php` in the installation of XenForo. Now continue with [step 4](https://github.com/rugk/threema-msgapi-sdk-php/wiki/How-to-generate-a-new-key-pair-and-send-a-message#user-content-4-generate-a-keypair-by-running-the-tool) of the official guide to create a public and private key.
3. Protect your private key file, so it is only readable by the process running PHP/XenForo and no other server user can read your private key file.
4. Also follow [step 5](https://github.com/rugk/threema-msgapi-sdk-php/wiki/How-to-generate-a-new-key-pair-and-send-a-message#5-request-custom-threema-id-and-submit-key) to request a new Gateway ID on <https://gateway.threema.ch>.

## 3. Continue setup with approved Gateway ID

1. When you got your Gateway ID, please go back to the ACP and enter the Gateway ID and the Gateway ID secret there.
2. Select the mode you used for your ID.
3. If you used the end-to-end encrypted mode you also have to add the path to your private key path into the input box under the "operation mode". When you followed the guide above and did not moved the private key file to another location the path might e.g. be this one: `threema-msgapi-sdk-php/privateKey.txt`
5. Now save your changes and when everything worked you should not see any error messages in the status area. Additionally you should be able to see your credits count there.

## 4. Enable two-factor-authentication

When everything is correct, you can enable the two-factor-authentication modes. To do this go to "Threema Gateway - Two-factor-authentication" and activate the modes you want to make available for your users.

If you installed libsodium you can also enable the option "Use libsodiums random number generator instead of XenForos internal one".
