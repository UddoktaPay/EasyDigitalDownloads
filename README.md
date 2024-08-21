# EDD Uddoktapay Gateway

EDD Uddoktapay Gateway is a WordPress plugin that integrates the Uddoktapay payment gateway with Easy Digital Downloads (EDD), enabling merchants to accept payments through Uddoktapay.

## Features

- Seamless integration with Easy Digital Downloads.
- Customizable gateway display name.
- Supports payment initiation and verification with Uddoktapay.
- Automatic currency conversion based on exchange rates.
- Easy setup and configuration.

## Download & Installation

### Download

Download [UddoktaPay.zip](https://github.com/UddoktaPay/EasyDigitalDownloads/releases/download/1.0.0/UddoktaPay.zip) file from the **Release** section of this repository.

### Installation

1. **Upload the Plugin**  
   Upload the downloaded plugin zip file through the WordPress admin dashboard:
   - Go to `Plugins` > `Add New`.
   - Click `Upload Plugin`.
   - Choose the downloaded zip file and click `Install Now`.

2. **Activate the Plugin**  
   After installation, click the `Activate` button to activate the plugin.

## Configuration

1. **Access Settings**  
   Go to `Downloads` > `Settings` > `Payment Gateways` > `Uddoktapay` to configure the plugin settings.

2. **Configure Uddoktapay Gateway**  
   - **Gateway Display Name**: Enter the name that will be displayed on the checkout page.
   - **API KEY**: Enter your Uddoktapay API key.
   - **API URL**: Enter the Uddoktapay API URL.
   - **Exchange Rate**: Set the exchange rate (e.g., 1 USD = ? BDT).

3. **Save Changes**  
   Click `Save Changes` to apply your settings.

## Usage

1. **Checkout with Uddoktapay**  
   When a customer selects Uddoktapay as the payment method during checkout, they will be redirected to the Uddoktapay payment page to complete the transaction.

2. **Payment Verification**  
   The plugin automatically handles payment verification and updates the order status accordingly.

## License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).