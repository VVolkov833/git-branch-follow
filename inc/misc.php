<?php
/*
Plugin Name: Custom Theme Updater
Description: Automatically updates your custom theme.
Version: 1.0.0
*/

// Hardcoded values
define('CUSTOM_THEME_REPO_OWNER', 'VVolkov833');
define('CUSTOM_THEME_REPO_NAME', 'memory-cards'); //memory-cards
define('CUSTOM_THEME_ACCESS_TOKEN', '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');//'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'); // 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
define('CUSTOM_THEME_VERSION_OPTION', 'custom_theme_version');


add_action( 'admin_menu', function() {
	add_options_page( 'My tests', 'My tests', 'switch_themes', 'my-tests', function() {
        if ( !current_user_can( 'administrator' ) ) { return; } // besides the switch_themes above, it is still needed
    
        $headers = array(
            'Authorization' => 'Bearer ' . CUSTOM_THEME_ACCESS_TOKEN,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        );
    
        //$response = wp_remote_get('https://api.github.com/repos/' . CUSTOM_THEME_REPO_OWNER . '/' . CUSTOM_THEME_REPO_NAME . '/tags', array('headers' => $headers));
        $response = wp_remote_get('https://api.github.com/repos/' . CUSTOM_THEME_REPO_OWNER . '/' . CUSTOM_THEME_REPO_NAME . '/branches/master', array('headers' => $headers));

        // Decode the JSON response
        $data = json_decode($response['body'], true);

        // Check if decoding was successful
        if ($data !== null) {
            // Access the information you need
            //$zipFileUrl = $data['commit']['commit']['tree']['url']; // Replace with the actual path to the zip file
            $zipFileUrl = 'https://api.github.com/repos/' . CUSTOM_THEME_REPO_OWNER . '/' . CUSTOM_THEME_REPO_NAME . '/zipball/master';
            $pushDateTimestamp = strtotime($data['commit']['commit']['author']['date']); // Replace with the actual timestamp field
        }

                ?>
                <div class="wrap">
                    <h2><?php echo get_admin_page_title() ?></h2>
                    <pre>
                    <?php
                    //echo 'https://api.github.com/repos/' . CUSTOM_THEME_REPO_OWNER . '/' . CUSTOM_THEME_REPO_NAME . '/tags'."\n";
                    echo 'https://api.github.com/repos/' . CUSTOM_THEME_REPO_OWNER . '/' . CUSTOM_THEME_REPO_NAME . '/branches/master'."\n";
                    print_r($headers);
                    print_r($response);
                    echo "Path to Zip File: $zipFileUrl\n";
                    echo "Push Date Timestamp: $pushDateTimestamp\n";


    // Headers for downloading the zip file
    $downloadHeaders = array(
        'Authorization' => 'Bearer ' . CUSTOM_THEME_ACCESS_TOKEN,
        //'Accept' => 'application/vnd.github+json',
        'X-GitHub-Api-Version' => '2022-11-28',
    );

    // Download the zip file
    $zipFileContents = wp_remote_get($zipFileUrl, array('headers' => $downloadHeaders));

    // Check if download was successful
    if (!is_wp_error($zipFileContents) && $zipFileContents['response']['code'] === 200) {
        // Save the zip file to the wp-content/upgrade folder
        $zipFilePath = WP_CONTENT_DIR . '/upgrade/' . CUSTOM_THEME_REPO_NAME . '-' . $pushDateTimestamp . '.zip';

        // Replace any spaces in the file name with underscores
        $zipFilePath = str_replace(' ', '_', $zipFilePath);

        // Save the file
        file_put_contents($zipFilePath, $zipFileContents['body']);

        // Output success message
        echo "Zip file saved to: $zipFilePath\n";

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) === TRUE) {
            // Create the tmp folder if it doesn't exist
            $tmpFolderPath = WP_CONTENT_DIR . '/upgrade/tmp';
            if (!file_exists($tmpFolderPath)) {
                mkdir($tmpFolderPath, 0755, true);
            }

            // Extract the contents to the tmp folder
            //$zip->extractTo($tmpFolderPath);
            // Extract the contents to the tmp folder without the top-level folder
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileInfo = pathinfo($filename);
                $targetPath = $tmpFolderPath . '/' . $fileInfo['basename'];
                copy("zip://$zipFilePath#$filename", $targetPath);
            }

/*
            // Check if there is only one top-level folder
            $firstFile = $zip->getNameIndex(0);
            $firstFolderName = pathinfo($firstFile)['dirname'];

            if ($zip->numFiles === 1 && $firstFolderName !== '.') {
                // If there is only one top-level folder, extract its contents
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $fileInfo = pathinfo($filename);
                    $targetPath = $tmpFolderPath . '/' . $fileInfo['basename'];
                    copy("zip://$zipFilePath#$filename", $targetPath);
                }
            } else {
                // If there are multiple files or no top-level folder, extract the contents directly
                $zip->extractTo($tmpFolderPath);
            }

//*/
/* get file content
if ($zip->open($zipFilePath) === TRUE) {
    // Read the content of the specified text file
    $textFileContent = $zip->getFromName($textFilePath);

    // Close the zip file
    $zip->close();
}
//*/

            // Close the zip file
            $zip->close();

            // Output success message
            echo "Zip file extracted to: $tmpFolderPath\n";
        } else {
            // Handle zip extraction error
            echo "Error extracting zip file\n";
        }
    } else {
        // Handle download error
        echo "Error downloading zip file\n";
    }


                    ?>
                    </pre>
                </div>
                <?php
            });
        });

// print the settings page
add_action( 'admin_init', function() {

    if ( !current_user_can( 'administrator' ) ) { return; }

    register_setting( 'Group', 'varname' );

});

return;


add_filter('site_transient_update_themes', 'custom_theme_check_for_update');

function custom_theme_check_for_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $theme_data = wp_get_theme('wp-theme-2');
    $current_version = $theme_data->get('Version');

    // Add authentication to the GitHub API request
    $headers = array(
        'Authorization' => 'Bearer ' . CUSTOM_THEME_ACCESS_TOKEN,
    );

    // Retrieve the latest version from your private repository
    $response = wp_remote_get(CUSTOM_THEME_REPO_URL . '/releases/latest', array('headers' => $headers));

    if (!is_wp_error($response) && $response['response']['code'] == 200) {
        $remote_info = json_decode($response['body'], true);

        // Extract version number from the tag name
        $remote_version = str_replace('v', '', $remote_info['tag_name']);

        if (version_compare($current_version, $remote_version, '<')) {
            $transient->response['wp-theme-2'] = array(
                'new_version' => $remote_version,
                'url' => $remote_info['html_url'],
                'package' => $remote_info['zipball_url'],
            );
        }
    }

    return $transient;
}

// Example usage of manual update check
// Uncomment the line below and visit your site to manually check for updates
// custom_theme_check_update_manually();
