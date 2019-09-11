# PrestaShop-Newsman
[Newsman](https://www.newsmanapp.com) plugin for PrestaShop. Sync your PrestaShop customers / subscribers to Newsman list / segments.

This is the easiest way to connect your Shop with [Newsman](https://www.newsmanapp.com).
Generate an API KEY in your [Newsman](https://www.newsmanapp.com) account, install this plugin and you will be able to sync your shop customers and newsletter subscribers with Newsman list / segments.

# Installation

## Prestashop 1.6x

Manual installation:
Copy the "newsman" directory from this repository to your "modules" shop directory.

## Prestashop 1.7x

Admin -> Module Manager -> Upload a module -> add "newsman.zip".

# Setup
1. Fill in your Newsman API KEY and User ID and click connect
![](https://raw.githubusercontent.com/Newsman/PrestaShop-Newsman/master/assets/api-setup-screen.png)

2. Choose destination segments for your newsletter subscribers and customer groups
All your groups will be listed and you can select the Newsman Segment to map to.
You can also choose to ignore the group or to upload the group members but include them in any segment.
For the segments to show up in this form, you need to set them up in your Newsman account first.
![](https://raw.githubusercontent.com/Newsman/PrestaShop-Newsman/master/assets/mapping-screen.png)

3. Choose how often you want your lists to get uploaded to Newsman
You can also do a manual synchronization by clicking "Synchronize now".
![](https://raw.githubusercontent.com/Newsman/PrestaShop-Newsman/master/assets/sync-screen.png)

For the automatic synchronization to work, you need to have the "native" "Cron tasks manager" (cronjobs) module installed and configured.

# Newsman Remarketing

# Installation

## Prestashop 1.6x

Manual installation:
Copy the "newsmanremarketing" directory from this repository to your "modules" shop directory.

## Prestashop 1.7x

Admin -> Module Manager -> Upload a module -> add "newsmanremarketing.zip".

## Setup
1. Fill in your Newsman Remarketing Tracking ID
![](https://raw.githubusercontent.com/Newsman/PrestaShop-Newsman/master/assets/1.jpg)

2. Enable and click save

