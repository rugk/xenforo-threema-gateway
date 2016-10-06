# Information for other add-on developers

You are free to create your own add-ons, which uses the Threema Gateway for your own purpose. However we obviously also appreciate it if you [create a Pull Request](../CONTRIBUTING.md) to extend the functionality to this add-on. It is a case-by-case decision whether to make something a in new add-on or include it here.

We follow [Semantic Versioning](http://semver.org/) to maintain backward compatibility. Additionally the version ID of this add-ons follows [the same format](https://xenforo.com/community/threads/development-best-practices.64996/#post-794344) as XenForo itself.

## General recommendations

*   During the installation of yours add-on, you should check whether this add-on is installed. You can see [here](https://xenforo.com/community/threads/checking-for-the-existance-of-other-add-ons-while-installing.113610/#post-1047354) how you can do it.

## Using the Handler

All files in the directory `Handler` are the files you have to look at. This matches the classes `ThreemaGateway_Handler_*`. These are also the only ones covered by the "Semantic Versioning" (as an API) as all others are not intended to be extended or called by other add-ons.

All files are PHPDOC documented and contain all necessary notes to find out what methods and other things you can use.
Note that `ThreemaGateway_Handler_PhpSdk` cannot be constructed by calling `new ThreemaGateway_Handler_PhpSdk`, but needs to be accessed with `ThreemaGateway_Handler_PhpSdk::getInstance()` as it is implemented as a [Singleton](https://en.wikipedia.org/wiki/Singleton_pattern). This also applies to `ThreemaGateway_Handler_Permissions`.
All other Handler functions can be accessed in the "usual" way.

When you want to access `ThreemaGateway_Handler_Settings` you should usually access it over `ThreemaGateway_Handler_PhpSdk->getSettings()`. However there may be cases where you want to access `ThreemaGateway_Handler_Settings` before `ThreemaGateway_Handler_PhpSdk`. (E.g. when you first want to check whether the SDK is ready (with `ThreemaGateway_Handler_Settings->isReady()`) before including the SDK.) If so you can create `ThreemaGateway_Handler_Settings`in the "usual" way, but please pass it to `ThreemaGateway_Handler_PhpSdk` when you call it to prevent resource-waste. (=bad performance)

#### Recommendations

As explained above you can use `ThreemaGateway_Handler_Settings->isReady()` to check whether the SDK is usable at all, before initiating it via `ThreemaGateway_Handler_PhpSdk`. You should do so.
Then you can create the neccessary handlers. All handlers usually check the permissions they require, but if you do not want to catch exceptions you should check all required permissions via `ThreemaGateway_Handler_Permissions->hasPermission()` before.
You may also consider checking `ThreemaGateway_Handler_Settings->isEndToEnd()` for security-sensitive data or when you want to test whether you can receive messages. (When the E2E mode is not set up you also cannot receive any messages.)

### Emoticons

The only handler you may want to call before sending a message is the [`Emoji`](../Handler/Emoji.php) handler. It allows you to add, respectively convert, all the smilies used in Threema. An example implementation of this can be found in the traditional 2FA method (see [`ThreemaGateway_Tfa_AbstractProvider->sendMessage()`](../Tfa/AbstractProvider.php).

## Helpers

The template helpers are also considered an "API" according to "Semantic Versioning". You can find them in the "Helper" dir. There also the name is mentioned you can use to access it in a template. They are available globally and obviously you can use them both in templates and in PHP files.  
Currently there are helpers for showing some regular expressions to evaluate Threema IDs and some helpers for displaying and converting public keys.
