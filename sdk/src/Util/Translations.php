<?php

namespace BlueMedia\OnlinePayments\Util;

class Translations
{
    private $translations = [
        'pl' => [
            'form.paywall.redirect' =>  'Trwa przekierowanie do Bramki Płatniczej Blue Media...',
            'form.paywall.javascript_disabled' => 'Masz wyłączoną obsługę JavaScript',
            'form.paywall.javascript_required' => 'Aby przejść do Bramki Płatniczej Blue Media, musisz włączyć obsługę JavaScript w przeglądarce.',
        ],
        'en' => [
            'form.paywall.redirect' =>  'You are being redirected to the Blue Media Payment Gateway...',
            'form.paywall.javascript_disabled' => 'You have disabled JavaScript',
            'form.paywall.javascript_required' => 'To access the Blue Media Payment Gateway, you need to enable JavaScript in your browser.',
        ],
        'de' => [
            'form.paywall.redirect' => 'Sie werden zum Blue Media Payment Gateway weitergeleitet...',
            'form.paywall.javascript_disabled' => 'Sie haben JavaScript deaktiviert',
            'form.paywall.javascript_required' => 'Damit du auf die zahlungspflichtige Seite Blue Media zugreifen kannst, aktiviere das JavaScript.',
        ],
    ];

    public function getTranslation($language)
    {
        if (array_key_exists($language, $this->translations) === false) {
            $language = 'pl';
            Logger::log(
                Logger::WARNING,
                sprintf('Unsupported language "%s" given for BlueMedia SDK. Setting Polish language as default.', $language)
            );
        }

        return $this->translations[$language];
    }
}
