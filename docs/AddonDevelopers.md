# Information for other add-on developers

You are free to create your own add-ons, which uses the Threema Gateway for your own purpose. However we obviously also appreciate it if you [create a Pull Request](../CONTRIBUTING.md) to extend the functionality to this add-on. It is a case-by-case decision whether to make something a in new add-on or include it here.

We follow [Semantic Versioning](http://semver.org/) to maintain backward compatibility. Additionally the version ID of this add-ons follows [the same format](https://xenforo.com/community/threads/development-best-practices.64996/#post-794344) as XenForo itself.

## Installation

During the installation of yours add-on, you should check whether this add-on is installed. You can see [here](https://xenforo.com/community/threads/checking-for-the-existance-of-other-add-ons-while-installing.113610/#post-1047354) how you can do it.

## Using the Handler

All files in the directory `Handler` are the files you have to look at. This matches the classes `ThreemaGateway_Handler_*`. These are also the only ones covered by the "Semantic Versioning" (as an API) as all others are not intended to be extended or called by other add-ons.

All files are PHPDOC documented and contain all necessary notes to find out what methods and other things you can use.
Note that `ThreemaGateway_Handler_PhpSdk` cannot be constructed by calling `new ThreemaGateway_Handler_PhpSdk`, but needs to be accessed with `ThreemaGateway_Handler_PhpSdk::getInstance()` as it is implemented as a [Singleton](https://en.wikipedia.org/wiki/Singleton_pattern). This also applies to `ThreemaGateway_Handler_Permissions`.
All other Handler functions can be accessed in the "usual" way.

When you want to access `ThreemaGateway_Handler_Settings` you should usually access it over `ThreemaGateway_Handler_PhpSdk->getSettings()`. However there may be cases where you want to access `ThreemaGateway_Handler_Settings` before `ThreemaGateway_Handler_PhpSdk`. (E.g. when you first want to check whether the SDK is ready (with `ThreemaGateway_Handler_Settings->isReady()`) before including the SDK.) If so you can create `ThreemaGateway_Handler_Settings`in the "usual" way, but please pass it to `ThreemaGateway_Handler_PhpSdk` when you call it to prevent resource-waste. (=bad performance)

#### Recommendations

As explained above you can use `ThreemaGateway_Handler_Settings->isReady()` to check whether the SDK is usable at all, before initiating it via `ThreemaGateway_Handler_PhpSdk`. You should do so.
Then you can create the necessary handlers. All handlers usually check the permissions they require, but if you do not want to catch exceptions you should check all required permissions via `ThreemaGateway_Handler_Permissions->hasPermission()` before.
You may also consider checking `ThreemaGateway_Handler_Settings->isEndToEnd()` for security-sensitive data or when you want to test whether you can receive messages. (When the E2E mode is not set up you also cannot receive any messages.)

### Emoticons

The only handler you may want to call before sending a message is the [`Emoji`](../Handler/Emoji.php) handler. It allows you to add, respectively convert, all the smilies used in Threema. An example implementation of this can be found in the traditional 2FA method (see [`ThreemaGateway_Tfa_AbstractProvider->sendMessage()`](../Tfa/AbstractProvider.php).
Smileys of received messages are stored as-is, which means they are stored UTF-8-enocoded as Unicode smileys.

## Helpers

The template helpers are also considered an "API" according to "Semantic Versioning". You can find them in the "Helper" dir. There also the name is mentioned you can use to access it in a template. They are available globally and obviously you can use them both in templates and in PHP files.  
Currently there are helpers for showing some regular expressions to evaluate Threema IDs and some helpers for displaying and converting public keys.

## Models

Usually you should only need to interact with the messages model (`ThreemaGateway_Model_Messages`). However you can (and should) even avoid this as there is also a Handler for it: `ThreemaGateway_Handler_Action_Receiver`. The handler can be used for the mayority of the cases.

However if you want to use the message model here are the basic steps you should keep in mind:
1. First you need to create the model and call `preQuery` if not already done before
2. Later you need to set all conditions you know via the `set...` methods.  
3. To receive the data you finally either need to call `getMessageDataByType` if you know the message type or (which is slightly slower) `getMessageMetaData` and afterwards pass the result to `getAllMessageData`.  
   If you only care about the message metadata you can however of course also only call `getMessageMetaData` (propably with `getMessageMetaData(true)` so that messages are grouped by their ID).

Notes:
*   For performance reasons `getAllMessageData` does one query for each message type of the messages, which is determinated by the meta data you have to pass to it. That's why it is always recommend to limit the amount of different message types you query. In the best case you already know the message type and can use `getMessageDataByType`.
*   Note that `setMessageId` may need a table prefix as the second parameter unless you only query the meta data via `getMessageMetaData`. What prefix to use (`message` or `metamessage`) depends on your query data. As a rule of thumb `metamessage` is good as long as your query includes the meta data.
*   The Handler `ThreemaGateway_Handler_Action_Receiver` can also serve as a good example  on how to query the message model. So if you want to do so, you may have a look at it.

## Listeners
It may be more effective to not always query the database for (new) messages, but to handle the messages directly after receiving them. Additionally if you have just some commands to handle you may even not want to save the messages in the database. Thanks to XenForo's Listeners model, both is easily possible.

In the ACP you can find these listeners created by the add-on:
*   `threemagw_message_callback_presave`
*   `threemagw_message_callback_postsave`

The name already shows when they run (before & after saving the message to the database) and they are also documented in the ACP. Usually you shouzld choose the post-save version over the pre-save one (as you do not have to pay attention to replay attacks there) unless you do not want to save the message in the database.
An example implementation of a simple listener for text messages can be seen in [`examples/MessageCallback.php`](examples/MessageCallback.php). 

As for the callback execution order, please choose one in accordance to these performance recommendations:
*   **0-100:** easy string checks (`$threemaId == 'ECHOECHO'` or `$threemaId == 'ECHOECHO'`) or other fast checks (`if (!$messageSaved) {…}`)
*   **100-200:** complex string checks (`preg_match('/^command/')`)
*   **>200:** database queries, file readings, external queries, …

Of course always the first checked condition matters as long as this condition is not easily satisfied.
