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
 * @todo: Support wordpress multisites(?)
 * @todo: Support wordpress subdirectory installations(?)
 *
 * All code is released under The Unlicense
 * See UNLICENSE in the root directory for the full text of The Unlicense
 * (or visit http://unlicense.org)
 */

define('NJEENY_SINGLE_CONF_GENERATE', 
  [
    'GENERAL' =>
      [
        'domain' => ['Enter the domain', []],
        'path' => ['Enter the path - no trailing slash', []],
        'subdomains' => ['Include Subdomains?', ['Yes', 'No']],
        'https' => ['Force HTTPS?', ['Yes', 'No']],
        'enable' => ['Also enable configuration?', ['Yes', 'No']],
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
            // www is not relevant in wordpress (enforceable through CMS)
            // @todo: any special stuff for wordpress?
          ],
        'none' =>
          [
            'www' => ['Force www subdomain?', ['Yes', 'No']],
            'php' => ['Do you need PHP support?', ['Yes', 'No']],
          ],
      ]
  ]
);

main();

/**
 * Main function.
 * This is the only function that is executed by our script
 * and delegates (almost) all the functionality to helper functions.
 */
function main() {
  // For now we can only generate configuration files, so only one option
  // We will add more stuff in the future (e.g. verify existing configurations)
  $main_question = questionOptionsGen('What do?', ['Generate Single file']);
  if ($main_question == 0)
    generateSingleConfMain();
}

/**
 * Helper function.
 * Generate a single nginx configuration file.
 */
function generateSingleConfMain() {
  $settings = array();
  foreach (NJEENY_SINGLE_CONF_GENERATE as $cat_name => $cat) {
    print generateCaption($cat_name);
    // Handle the "autodetect" option before processing the category
    // or even setting the category to process
    if (!empty($settings['cms']) && $settings['cms'] == 'autodetect')
      cmsAutodetect($settings);
    // For now we can do some hacky coding to determine
    // which category we're in
    $cat_to_process = empty($settings['cms']) ? $cat : $cat[$settings['cms']];
    processCategory($cat_to_process, $settings);
  }

  // Generate and print the result and that's it for this type of functionality.
  $result = generateConfFile($settings);
  print "\n$result\n";
}

/**
 * Helper function.
 * Given the settings, generates a configuration file.
 * @param array The settings' array
 * @return string A result message
 */
function generateConfFile($settings) {
  $contents = '';
  // Wrap everything in a try-catch block so that PHP can do
  // the heavy lifting of the exception handling for us
  try {
    $contents .= generateRedirects($settings);
  } catch (Exception $e) {
    return $e->getMessage();
  }
}

/**
 * Helper function.
 * Given the settings' array generates the redirects that are required
 * in order to respect our user's wishes.
 * @todo: There must be a cleaner/more elegant way to do this.
 * @param array The settings' array
 * @return string Redirection servers for our configuration.
 */
function generateRedirects($settings) {
  $contents = '';
  if (isset($settings['www']) && $settings['www'] == 0) {
    if ($settings['https'] == 0) { // + https + www
      $contents .= "\n";
      $contents .= generateRedirectServer($settings['domain'], FALSE, ($settings['subdomains'] == 0), TRUE, TRUE);
      $contents .= "\n";
      $contents .= generateRedirectServer($settings['domain'], TRUE, ($settings['subdomains'] == 0), TRUE, TRUE);
      $contents .= "\n";
    } else { // - https + www
      $contents .= "\n";
      $contents .= generateRedirectServer($settings['domain'], FALSE, ($settings['subdomains'] == 0), TRUE, FALSE);
      $contents .= "\n";
    }
  } else {
    if ($settings['https'] == 0) { // + https - www
      $contents .= "\n";
      $contents .= generateRedirectServer($settings['domain'], FALSE, ($settings['subdomains'] == 0), FALSE, TRUE);
      $contents .= "\n";
    } else { // - https - www
      // No redirects needed
    }
  }

  return $contents;
}

/**
 * Helper function.
 * @param string The name of the server
 * @param bool Whether the SOURCE of the redirect is 443
 * @param bool Whether we need to include subdomains
 * @param bool Whether we need to force www
 * @param bool Whether the destination of the redirect is a secure location
 * @return string A proper nginx server configuration matching the input
 */
function generateRedirectServer($server_name, $https, $subdomains, $www, $secure_dest) {
  $output  = "server {\n";
  $output .= "\tlisten\t";
  $output .= $https ? "443 ssl;\n" : "80;\n";
  $output .= "\tserver_name\t$server_name";
  $output .= $subdomains ? " *.$server_name;\n" : ";\n";
  $output .= $https ? "\t" . generateHTTPSDefaults($server_name) . "\n" : "";
  $output .= "\treturn\t301 http";
  $output .= $secure_dest ? "s" : "";
  $output .= "://";
  $output .= $www ? "www." : "";
  $output .= $server_name . '$request_uri;' . "\n";
  $output .= "}";
  return $output;
}

/**
 * Helper function.
 */
function generateHTTPSDefaults($server_name) {
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
  $wordpress_path = $settings['path'] . '/wp-config.php';
  if (file_exists($drupal_path)) {
    $settings['cms'] = 'drupal';
    // We only support D7 and D8
    // If we need to do something differently for D8, below is a method to do it:
    // $drupal_path_v8 = $settings['path'] . '/sites/default/default.services.yml';
    // $settings['cms_version'] = file_exists($drupal_path_v8) ? 8 : 7;
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
 * Generate an one-line configuration entry.
 * Useful in cases where creating a template file would be too much of a hassle.
 * @param array An assoc array with the conf entry as key and the value(s) in an array
 * @return string The formatted entry (with the closing semicolon)
 */
function generateConfEntry($data) {
  $output = '';
  foreach ($data as $key => $value) {
    $values = implode(" ", $value);
    $output .= "\t$key\t$values;\n";
  }
  return trim($output);
}

/**
 * Helper function.
 * Reads the contents of a template file and makes the necessary substitutions.
 * @param string The name of the file (no extension)
 * @param array Assoc array with substitutions (key replaced by value)
 * @return string The processed template file's contents.
 */
function processTemplate($template, $replacements = array()) {
  $path = "templates/$template.tpl";
  $content = file_get_contents($path);
  foreach ($replacements as $key => $value) {
    $content = str_replace($key, $value, $content);
  }
  return $content;
}
