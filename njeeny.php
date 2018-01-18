<?php

/**
 * @file
 * Sensible and secure nginx configurations.
 * Supports Drupal and Wordpress.
 * @todo: Add an option for http server authentication.
 * @todo: Add an option to scan existing configurations and fix them.
 * @todo: Add more content management systems (?).
 * @todo: Add an option for default server config
 * @todo: Maybe support the generation of the https certificate too?
 * @todo: Maybe add some kind of option sanity check in the future?
 * @todo: Maybe add the option to mass create configurations?
 *
 * All code is released under The Unlicense
 * See UNLICENSE in the root directory for the full text of The Unlicense
 * (or visit http://unlicense.org)
 */

define('NJEENY_STRUCTURE', 
  [
    'GENERAL' =>
      [
        'domain' => ['Enter the domain', []],
        'path' => ['Enter the path - no trailing slash', []],
        'subdomains' => ['Include Subdomains?', ['Yes', 'No']],
        'https' => ['Force HTTPS?', ['Yes', 'No']],
        'cms' => ['CMS', ['autodetect', 'drupal', 'wordpress', 'none']],
      ],
    'CMS SPECIFIC' =>
      [
        'drupal' => 
          [
            'www' => ['Force www subdomain?', ['Yes', 'No']],
            'advagg' => ['Enable advagg support?', ['Yes', 'No']],
          ],
        'wordpress' =>
          [
            // @todo: any special stuff for wordpress?
          ],
        'none' =>
          [
            'php' => ['Do you need PHP support?', ['Yes', 'No']],
          ],
      ]
  ]
);

main();

function main() {
  $settings = array();
  foreach (NJEENY_STRUCTURE as $cat_name => $cat) {
    print generateCaption($cat_name);
    // For now we can do some hacky coding to determine
    // which category we're in
    $cat_to_process = empty($settings['cms']) ? $cat : $cat[$settings['cms']];
    processCategory($cat_to_process, $settings);
  }
}

/**
 * Helper function.
 * Processes a category.
 * @param array Associative array with the questions that need to be asked
 * @param array The settings' array
 * @return void
 */
function processCategory($cat, &$settings) {
  foreach ($cat as $setting_name => $setting) {
    // We need to return the key and not the index in the cms choice question
    $return_index = ($setting_name != 'cms');
    $settings[$setting_name] = empty($setting[1]) ?
                               questionInputGen($setting[0]) :
                               questionOptionsGen($setting[0], $setting[1], $return_index);
  }
}

/**
 * Helper function.
 * Given the settings so far, determines the CMS and updates the settings.
 * Functionality required by the "autodetect" feature.
 * @param array The settings' array
 * @return void
 */
function cmsAutodetect(&$settings) {
  $settings['cms'] = 'none';
  $drupal_path = $settings['path'] . '/sites/default/default.settings.php';
  $drupal_path_v8 = $settings['path'] . '/sites/default/default.services.yml';
  $wordpress_path = $settings['path'] . '/wp-config.php';
  if (file_exists($drupal_path)) {
    $settings['cms'] = 'drupal';
    // We only support D7 and D8
    $settings['cms_version'] = file_exists($drupal_path_v8) ? 8 : 7;
  }
  if (file_exists($wordpress_path))
    $settings['cms'] = 'wordpress';
}

/**
 * Helper function.
 * Asks a question.
 * @param string Message to the user (no new line)
 * @return string The user's input
 */
function questionInputGen($message) {
  print "$message\n";
  $line = trim(fgets(STDIN));
  return $line;
}

/**
 * Helper function.
 * Given a message and some options it presents the options
 * to the user, and makes sure that one of the valid values
 * is provided.
 * @param string Message to the user (no new line)
 * @param array Array of the different options
 * @param bool Return the index (TRUE) or the array item
 * @return int The index of the user's reply
 */
function questionOptionsGen($message, $options, $return_index = TRUE) {
  $valid_answer = false;
  $extra_notes = '(first answer is always default)';
  while (!$valid_answer) {
    print "$message $extra_notes\n";
    $option_message = '';
    $index = 0;
    foreach ($options as $option) {
      $option_message .= "[$index] $option\n";
      $index++;
    }
    print $option_message;
    $line = trim(fgets(STDIN));
    $oindex = intval($line);
    if ($oindex < count($options))
      return $return_index ? $oindex : $options[$oindex];
  }
}

/**
 * Helper function.
 * Create a presentable caption.
 * @param string Message to the user (no new line)
 * @return string An "underlined" version of the caption.
 */
function generateCaption($message) {
  $caption = strtoupper($message) . "\n";
  for ($i = 0; $i < strlen($message); $i++)
    $caption .= "=";
  $caption .= "\n";
  return $caption;
}

/**
 * Helper function.
 * Reads the contents of a template file and makes the necessary substitutions.
 * @param string The name of the file (no extension)
 * @param array Assoc array with substitutions (key replaced by value)
 * @return string The processed template file's contents.
 */
function processTemplate($template, $replacements) {
  $path = "templates/$template.tpl";
  $content = file_get_contents($path);
  foreach ($replacements as $key => $value) {
    $content = str_replace($key, $value, $content);
  }
  return $content;
}
