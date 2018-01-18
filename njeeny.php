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
        'path' => ['Enter the path', []],
        'subdomains' => ['Include Subdomains?', ['Yes', 'No']],
        'https' => ['Force HTTPS?', ['Yes', 'No']],
        'cms' => ['CMS', ['Autodetect', 'drupal', 'wordpress', 'none']],
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
  foreach (NJEENY_STRUCTURE as $key => $value) {
    
  }
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
 */
function generateCaption($message) {
  $caption = strtoupper($message) . "\n";
  for ($i = 0; $i < strlen($message); $i++)
    $caption .= "=";
  $caption .= "\n";
  return $caption;
}
