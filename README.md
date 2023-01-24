# Stackoverflow Slack Connector

This small tool can do scheduled runs to fetch the newest questions in 
StackOverflow (with special tags) and put it as message into Slack channels.

## Installation

1. Download
2. Set up a [webhook](https://api.slack.com/messaging/webhooks) for a Slack channel of
   your choice and take note of the webhook URL.
3. Copy the webhooks-example.ini file to webhooks.ini.
4. Paste the webhook URL into the webhooks.ini file.
5. Optionally, register an app with [Stack App](https://stackapps.com/apps/oauth/register),
   note the app key, and save it to the key.txt file to bypass StackOverflow API rate limitations.

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
   in the document tree until cache cleared" has appeared in your Slack channel.
5. Optionally, register an app with [Stack App](https://stackapps.com/apps/oauth/register),
   note the app key, and run
   ```
   composer install
   SLACK_WEBHOOK_URL="<webhook-url>" STACK_APPS_KEY="<app-key>" ./vendor/bin/phpunit --group end-to-end
   ```
   to bypass StackOverflow API rate limitations.