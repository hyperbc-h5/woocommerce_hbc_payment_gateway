# Hbc Payment Woocommerce

A WooCommmerce Extension inspired by [Create Woo Extension](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/create-woo-extension/README.md).

## Getting Started

### Prerequisites

-   [NPM](https://www.npmjs.com/)
-   [Composer](https://getcomposer.org/download/)
-   [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)

### Installation and Build

```
npm install
npm run build
wp-env start
```

Visit the added page at http://localhost:8888/wp-admin/admin.php?page=wc-admin&path=%2Fexample.
# hyperbc_woocommerce_extension

### Release
zip -r hbc_payment_woocommerce_0.1.0.zip ./hyperbc-payment-woocommerce/ -x "hbc-payment-woocommerce/.git/**" -x "hbc-payment-woocommerce/node_modules/**" -x "hbc-payment-woocommerce/vendor/**" -x "hbc-payment-woocommerce/composer.phar"