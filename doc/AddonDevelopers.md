# Information for other add-on developers

You are free to create your own add-ons, which uses the Threema Gateway for your own purpose. However we obviously also appreciate it if you [create a Pull Request](../CONTRIBUTING.md) to extend the functionality to this add-on. It is  a case-by-case decision whether to make something a in new add-on or include it here.

We follow [Semantic Versioning](http://semver.org/) to maintain backward compatibility. Additionally the version ID of this add-ons follows [the same format](https://xenforo.com/community/threads/development-best-practices.64996/#post-794344) as XenForo itself.

## General recommendations

*   During the installation of yours add-on, you should check whether this add-on is installed. You can see [here](https://xenforo.com/community/threads/checking-for-the-existance-of-other-add-ons-while-installing.113610/#post-1047354) how you can do it.

## Using the Handler

The file [`Handler.php`](../Handler.php) and all files in the directory `Handler` are the files you have to look at. This matches the classes `ThreemaGateway_Handler` and `ThreemaGateway_Handler_*`. These are also the only ones covered by the "Semantic Versioning" (as an API) as  all others are not intended to be extended or called by other add-ons.

All files are PHPDOC documented and contain all necessary notes to find out what methods and other things you can use. Basically you most only need to use `ThreemaGateway_Handler` as this class has implements all important functions of the Threema Gateway like sending messages or fetching public keys.

#### Recommendations

After you created the main handler you should make sure to call `isAvaliable()` first and abort if the Gateway is not available. Afterwards you should check all required permissions via `hasPermission()`. But you should also be aware that already the `__construct` method may throw exceptions if the Threema Gateway is not correctly set up.  
You may also consider checking `isEndToEnd()` for security-sensitive data or when you want to test whether you can receive messages. (When the E2E mode is not set up you cannot receive any messages.)

The methods for sending simple messages and E2E messages are deliberately separated. You can see how to automatically choose the method in [`ThreemaGateway_Tfa_AbstractProvider->sendMessage()`](../Tfa/AbstractProvider.php).

### Emoticons

The only handler you may want to call before sending a message is the [`Emoji`](../Handler/Emoji.php) handler. It allows you to add respectively convert all the smilies used in Threema. An example implementation of this can be found in the traditional 2FA method (see [`ThreemaGateway_Tfa_AbstractProvider->sendMessage()`](../Tfa/AbstractProvider.php).

## Helpers

The template helpers are also considered an "API" according to "Semantic Versioning". You can find them in "Helper" where there is the name mentioned you can use to access it in a template. They are available globally and obviously you can use them both in templates and in PHP files.  
Currently there are helpers for showing some regular expressions to evaluate Threema IDs and some helpers for displaying and converting public keys.
