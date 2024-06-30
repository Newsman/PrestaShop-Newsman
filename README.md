# PrestaShop-NewsMAN 1.6.x-1.7.x-1.8.x
Presenting the [NewsMAN](https://www.newsman.com) plugin designed for PrestaShop. Effortlessly synchronize your PrestaShop customers/subscribers with the NewsMAN list/segments. This offers the most straightforward way to integrate your store with NewsMAN. Generate an API KEY within your NewsMAN account, install the plugin, and efficiently synchronize your store's customers and newsletter subscribers with NewsMAN list/segments.

# Installation

## Prestashop 1.6x

Manual installation:
Copy the "newsmanapp" directory from this repository "src/install" to your "modules" shop directory.
Copy `newsmanfetch.php` from `../install/newsmanfetch.php` to root of your prestashop installation

## Prestashop 1.7x, 8.x

Admin -> Module Manager -> Upload a module -> add "newsmanapp.zip" from "src/install".
Copy `newsmanfetch.php` from `../install/newsmanfetch.php` to root of your prestashop installation

# Setup
1. Input your Newsman API KEY and User ID, and proceed by clicking the "Connect" button:
![](https://raw.githubusercontent.com/Newsman/PrestaShop-Newsman/master/assets/api-setup-screen.png)

2. Pick the destination segments for your newsletter subscribers and customer groups. Your various groups will be presented, enabling you to designate the appropriate NewsMAN Segment for alignment. Alternatively, you can opt to exclude the group or upload its members while still incorporating them into any segment. To have these segments appear in this form, make sure to set them up within your NewsMAN account beforehand. 
![](https://raw.githubusercontent.com/Newsman/PrestaShop-Newsman/master/assets/mapping-screen.png)

3. Select the frequency at which you prefer your lists to be uploaded to NewsMAN. Additionally, you have the option to manually synchronize by clicking "Synchronize now."
![](https://raw.githubusercontent.com/Newsman/PrestaShop-Newsman/master/assets/sync-screen.png)

To enable automated synchronization, make sure you have the "native" "Cron tasks manager" (cronjobs) module installed and configured appropriately.

# Sync Segmentation

- Newsletter Subscribers: email, newsletter_date_add, source
- Customers with Newsletter: email, firstname, lastname, gender, birthday, source

# Newsman Remarketing

## Setup
1. Fill in your Newsman Remarketing Tracking ID
![](https://raw.githubusercontent.com/Newsman/PrestaShop-Newsman/master/assets/1.jpg)

2. Enable and click save

Once the plugin is installed, you will also experience automatic implementation of feed products and events such as product impressions, AddToCart, and purchases.

# Plugin Description Features

## Subscription Forms & Pop-ups
- Craft visually appealing forms and pop-ups to engage potential leads through embedded newsletter signups or exit-intent popups.
- Maintain uniformity across devices for a seamless user experience.
- Integrate forms with automations to ensure swift responses and the delivery of welcoming emails.

## Contact Lists & Segments
- Efficiently import and synchronize contact lists from diverse sources to streamline data management.
- Apply segmentation techniques to precisely target audience segments based on demographics or behavior.

## Email & SMS Marketing Campaigns
- Effortlessly send out mass campaigns, newsletters, or promotions to a broad subscriber base.
- Customize campaigns for individual subscribers by incorporating their names and suggesting relevant products.
- Re-engage subscribers by reissuing campaigns to those who haven't opened the initial email.

## Email & SMS Marketing Automation
- Automate personalized product recommendations, follow-up emails, and strategies to address cart abandonment.
- Strategically tackle cart abandonment or highlight related products to encourage completed purchases.
- Collect post-purchase feedback to gauge customer satisfaction.

## Ecommerce Remarketing
- Reconnect with subscribers through targeted offers based on past interactions.
- Personalize interactions with exclusive offers or reminders based on user behavior or preferences.

## SMTP Transactional Emails
- Ensure the timely and reliable delivery of crucial messages, such as order confirmations or shipping notifications, through SMTP.

## Extended Email and SMS Statistics
- Gain comprehensive insights into open rates, click-through rates, conversion rates, and overall campaign performance for well-informed decision-making.

The NewsMAN Plugin for PrestaShop simplifies your marketing efforts without hassle, enabling seamless communication with your audience.
