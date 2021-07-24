<?php

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;

// get primary language from the browser
$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

$translator = new Translator($lang, new MessageSelector());
// set the fallback, depending on the conference, from the config file
$translator->setFallbackLocales([$settings['settings']['fallback_language']]);

// Add a loader that will get the php files we are going to store our translations in
// we could use xlf or yaml instead: https://symfony.com/doc/current/translation.html#basic-translation
$translator->addLoader('php', new PhpFileLoader());

// Add language files here
$lang_path = '../lang/';
$settings = require __DIR__.'/../cfg/settings.php';
if ($settings['settings']['website_type'] == 'workshop')
{
    $lang_path .= 'workshop/';
}
$translator->addResource('php', $lang_path.'en.php', 'en'); // English
$translator->addResource('php', $lang_path.'fr.php', 'fr'); // French
$translator->addResource('php', $lang_path.'de.php', 'de'); // German

$container['view']->getEnvironment()->addExtension(new TranslationExtension($translator));

?>
