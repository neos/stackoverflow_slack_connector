# Stackoverflow Slack Connector

This small tool can do scheduled runs to fetch the newest questions in 
StackOverflow (with special tags) and put it as message into Slack channels.

## Installation

1. Download
2. Set up a [webhook](https://api.slack.com/messaging/webhooks) for a Slack channel of
   your choice and take note of the webhook URL.
3. Copy the webhooks-example.ini file to webhooks.ini.
4. Paste the webhook URL into the webhooks.ini file.
5. Run
   ```
   php PostNewQuestions.php
   ```
6. Optionally, register an app with [Stack App](https://stackapps.com/apps/oauth/register),
   note the app key, and save it to the key.txt file to bypass StackOverflow API rate limitations.
7. Optionally, use the [legacy](https://api.slack.com/reference/messaging/attachments#legacy_fields) 
   instead of the latest [block](https://api.slack.com/reference/messaging/attachments#fields) Slack 
   message structure by running:
   ```
   messagestructure="legacy" php PostNewQuestions.php
   ```
   They differ slightly in appearance.

## Testing

### Unit Tests

1. Download
2. Run
   ```
   composer install
   ./vendor/bin/phpunit --group unit
   ```

### End-to-End Tests

1. Download
2. Set up a [webhook](https://api.slack.com/messaging/webhooks) for a Slack channel of 
   your choice and take note of the webhook URL.
3. Run
   ```
   composer install
   SLACK_WEBHOOK_URL="<webhook-url>" ./vendor/bin/phpunit --group end-to-end
   ```
4. Confirm that the StackOverflow question "Neos CMS 7: Newly created node disappears 
   in the document tree until cache cleared" has appeared twice in your Slack channel:
   The first one is rendered with the block and the second one with the legacy Slack 
   message structure.
5. Optionally, register an app with [Stack App](https://stackapps.com/apps/oauth/register),
   note the app key, and run
   ```
   composer install
   SLACK_WEBHOOK_URL="<webhook-url>" STACK_APPS_KEY="<app-key>" ./vendor/bin/phpunit --group end-to-end
   ```
   to bypass StackOverflow API rate limitations.
6. Optionally, restrict end-to-end testing to a specific "block" or "legacy" message structure with
   ```
   composer install
   SLACK_MESSAGE_STRUCTURE="<message-structure>" SLACK_WEBHOOK_URL="<webhook-url>" STACK_APPS_KEY="<app-key>" ./vendor/bin/phpunit --group end-to-end
   ```