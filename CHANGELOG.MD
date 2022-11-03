# Changelog

## [2.8.2] 02.11.2022
- Change the display of information about an available module update
- Change the behavior of statuses when the product is out of stock
- Added amplitude analytics events
- Refactoring config constants
- Improved status changes when ordering multiple delivery methods

## [2.8.1] 18.10.2022
- Fixed blik status autoupdate
- Fixed blik analytics
- Fixed e-mail bluemedia statuses in prestashop < 1.7.7.4
- Fixed payment status in history order
- Fixed error displaying payments in checkout in older prestashop version

## [2.8.0] 30.09.2022
- Code refactoring
- Updated amplitude events
- Corrected translations

## [2.7.9] 8.09.2022
- Fixed error displaying payments in thecheckout module
- Fixed bug blocking modal display
- Code refactoring
- Ability to translate card payment method
- Updated amplitude events
- Fixed bugs main gateway redirect
- Corrected translations
- Adding namespace
- Improved code standards

## [2.7.8] 2.08.2022
- Fixed bugs main gateway redirect

## [2.7.7] 1.08.2022
- Added Google Analytics tracking 
- Added Amplitude tracking 
- Added promote channels
- Added display of tags promoting installments
- Modified sql queries
- Added support for payment channels from the Czech and Slovak regions
- Fixed bugs
- Czech and Slovak language added 
- Added help tab in module administration

## [2.7.6] 9.06.2022
- Fixed bugs in older version prestashop

## [2.7.4] 2.05.2022
- Fixed multistore
- Changes backend options
- Change frontend design 
- Refactoring the SDK connection
- Refactoring javascript classes and libraries
- Added backend helpers
- Changing the payment method selection
- Promotions of selected payment methods
- Increasing analytical capabilities by adding data collecting events
- Update vendors

## [2.7.3] 16.03.2022
- Fixed multistore 
- Improved google analytics by increasing analytics events
- Refractorization of classes
- Added backend helpers
- Update vendors

## [2.7.2] 23.02.2022
- Fixed onepagecheckout redirect payments
- Added redirect options for BLIK and Google Pay
- Improved grid payments
- Update vendors

## [2.7.1] 09.02.2022
- Fixed duplicate payments
- Fixed issues with displaying some payment channels
- Added deletion of inactive payments assigned to the account

## [2.7.0] 23.01.2022
- Fixed a group creation error during module installation
- Fixed popup payments in backend
- Added ajax saved configuration
- Changed the way to check for new version availability via API
- Added ajax controller 
- Changes in css file location

## [2.6.9] 21.01.2022
- A major redesign of the module management panel
- Grouping of payments
- Notification of availability of a new version

## [2.6.7] 15.11.2021
- Payment return communication added
- Changing the name of the module

## [2.6.6] 27.09.2021
- The security of the module has been improved

## [2.6.5] 7.09.2021
- Fixed payment form

## [2.6.4] 30.08.2021
- Fixed language problems
- Hide payments if only credit cards are available

## [2.6.3] 25.08.2021
- Fixed status installations when updating a module
- Improved payment status 

## [2.6.2] 19.08.2021
- Using composer package manager for better organization
- Add autoloader class
- Fixed ITN problem  
- Fixed payment buttons language
- Creation of native transactional messages
- Change of default settings of transaction statuses
- Change classes directory to 'src'
- Changing the location of payment methods. Payments are located in the main module configuration
- Improved appearance of customer-side payment methods

## [2.6.1] 21.04.2021
### Changed
- Min and max amount for Smarney payments

### Added
- Compatibility with PHP 7.0

## [2.6.0] 23.02.2021
### Added
- Another option to connect with paywall

## [2.5.1] 21.01.2021
### Fixed
- Wrong language after redirecting to paywall

## [2.5.0] 15.12.2020
### Added
- Additional information text on thank you page

## [2.4.0] 03.12.2020
### Added
- New Payment Channel - Smartney

## [2.3.4] 13.08.2020
### Fixed
- Google Pay mobile payments

## [2.3.3] 24.06.2020
### Fixed
- Typo in button text

## [2.3.2] 20.05-2020
### Fixed
- Duplicated order payment in admin panel

## [2.3.1] 07.04-2020
### Added
- Translations for redirect to BlueMedia paywall

### Fixed
- Redirect for payment channels with PSD regulations

## [2.3.0] 19-03-2020
### Added
- License blocks
- Language support
- English translations
- PSD 2 agreements for defined payment channels
- Monolog Logger

## Deleted
- Return types for compatibility with PHP 5.6
- ITN statuses logic after payment
