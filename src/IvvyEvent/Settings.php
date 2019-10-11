<?php

namespace IvvyEvent;

/**
 * iVvy Events
 *
 * @category   Event
 * @package    iVvy WordPress Project
 * @subpackage Event Registration
 * @copyright  Copyright (c) iVvy Pty Ltd
 * @version    $Id$
 */

/**
 * Class for handling plug-in settings page in WordPress Admin
 *
 * @category   Event
 * @package    iVvy WordPress Project
 * @subpackage Event Registration
 * @copyright  Copyright (c) iVvy Pty Ltd
 */
class Settings
{
    /**
     * Module Name
     */
    const MODULE = 'ivvy';

    /**
     * Options group name used for saving data in group
     *
     * @var string
     */
    const OPTION_GROUP = 'ivvy_setting';

    /**
     * Options Data will be stored by this key
     *
     * @var string
     */
    const OPTION_SAVE_KEY = 'ivvy_event_reg_options';

    /**
     * Nonce key for form security
     *
     * @var string
     */
    const NONCE_KEY = 'ivvy_op_verify';

    /**
     * Slug for ivvy settings page
     *
     * @var string
     */
    const SETTING_PAGE_SLUG = 'ivvy_event_reg_settings';

    /**
     * Ivvy Domain options
     *
     * @var type
     */
    public static $ivvyDomains = array(
        'https://www.ivvy.com.au' => 'iVvy Australia',
        'https://www.ivvy.com' => 'iVvy US',
        'https://www.ivvy.co.uk' => 'iVvy UK',
        'https://www.ivvy.co.nz' => 'iVvy New Zealand',
    );

    /**
     * Initialize and add setting page related hooks
     *
     * @return void
     */
    public function init()
    {
        // Register a new setting for page
        register_setting(self::OPTION_GROUP, self::OPTION_SAVE_KEY);

        // register a new section in the "ivvySetting" page
        add_settings_section(
            'generalSection',
            __('General', 'iVvy'),
            array($this, 'displayGeneralSectionDescription'),
            self::SETTING_PAGE_SLUG
        );

        // Add fields to general section
        foreach (self::getGeneralFields() as $fieldId => $field) {
            $fieldMethod = 'displayField';

            // Register a field in the section, inside the page
            add_settings_field(
                $fieldId,
                __($field['label'], self::MODULE),
                array($this, $fieldMethod),
                self::SETTING_PAGE_SLUG,
                'generalSection',
                $field
            );
        }
    }

    /**
     * Display General Section Description
     *
     * @param type $args
     */
    public function displayGeneralSectionDescription($args)
    {
        ?>
        <p id="<?= esc_attr($args['id']); ?>">
        <?= esc_html__('General Setting for enabling iVvy Event Registration', self::MODULE); ?>
        </p>
        <?php
    }

    /**
     * Callbase function for displaying field
     *
     * @param array $args
     */
    public function displayField($args)
    {
        // Output the field
        switch ($args['type']) {
            case 'static':
                $field = sprintf('<p id="%1$s" class="%3$s">%2$s</p>',
                    esc_attr($args['label_for']),
                    $this->getFieldValue($args['label_for']),
                    esc_attr($args['field_class'])
                );
                break;

            case 'select':
                $field = $this->selectFieldHtml($args);
                break;

            default:
                $field = sprintf('<input id="%1$s" name="%2$s[%1$s]" value="%3$s" class="%4$s" />',
                    esc_attr($args['label_for']),
                    self::OPTION_SAVE_KEY,
                    $this->getFieldValue($args['label_for']),
                    esc_attr($args['field_class'])
                );
                break;
        }

        $description = '';
        if ($args['description']) {
            $description = sprintf('<p class="description">%s</p>',
                esc_attr($args['description'])
            );
        }

        // Print the html on page
        echo $field . $description;
    }

    /**
     * Returns html for select element
     *
     * @param array $args
     * @return string
     */
    public function selectFieldHtml($args)
    {
        $html = sprintf('<select id="%1$s" name="%2$s[%1$s]" class="%3$s">',
            esc_attr($args['label_for']),
            self::OPTION_SAVE_KEY,
            esc_attr($args['field_class'])
        );
        $fieldValue = $this->getFieldValue($args['label_for']);
        foreach ($args['multiOptions'] as $value => $label) {
            $html .= sprintf('<option value="%1$s"%2$s>%3$s</option>',
                $value,
                $fieldValue == $value ? ' selected="selected"' : '',
                $label
            );
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Returns the value of the setting field
     *
     * @param string $field
     * @return string
     */
    public static function getFieldValue($field)
    {
        // Get the value of the setting we've registered with register_setting()
        $options = get_option(self::OPTION_SAVE_KEY);
        switch ($field) {
            case 'registrationPageUrl':
                return self::getRegistrationPageUrl();
            default:
                return $options[$field];
        }
    }

    /**
     * Return registration page Url
     *
     * @return string
     */
    public static function getRegistrationPageUrl()
    {
        $options = get_option(self::OPTION_SAVE_KEY);
        return $options['registrationPageUrl']
            ? $options['registrationPageUrl']
            : get_permalink(IvvyActivate::getRegistrationPageId());
    }

    /**
     * Adds the setting page menu item
     *
     * @return void
     */
    public function addIvvyEventRegSettingPage()
    {
        add_options_page(
            'iVvy Event Settings',
            'iVvy Event',
            'manage_options',
            self::SETTING_PAGE_SLUG,
            array($this, 'displaySettingsPage')
        );
    }

    /**
     * Display html of setting page
     *
     * @return void
     */
    public function displaySettingsPage()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if the user have submitted the settings
        // wordpress will add the "settings-updated" $_GET parameter to the url
        if (isset($_GET['settings-updated'])) {
            // add settings saved message with the class of "updated"
            add_settings_error('ivvy_messages', 'ivvy_message', __('Settings Saved', 'ivvy'), 'updated');
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // output security fields for the registered setting "ivvySetting"
                settings_fields(self::OPTION_GROUP);

                // output setting sections and their fields
                // (sections are registered for "ivvySetting", each field is registered to a specific section)
                do_settings_sections(self::SETTING_PAGE_SLUG);

                // output save settings button
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Returns the iVvy domain
     *
     * @return string
     */
    public static function getIvvyDomain()
    {
        if (ENV_DEVELOPMENT) {
            return 'https://www.vagrant.bluehype.loc';
        }
        $ivvyDomain = \IvvyEvent\Settings::getFieldValue('ivvyDomain');
        return $ivvyDomain ?: IVVY_BASE_URL;
    }

    /**
     * Returns General Fields
     *
     * @return array
     */
    public static function getGeneralFields()
    {
        return array(
            'ivvyDomain' => array(
                'label_for' => 'ivvyDomain',
                'field_class' => '',
                'class' => 'row',
                'label' => 'Account Country',
                'type' => 'select',
                'description' => 'Select country in which your account is.',
                'multiOptions' => self::$ivvyDomains,
            ),
            'accountDomain' => array(
                'label_for' => 'accountDomain',
                'field_class' => 'regular-text',
                'class' => 'row',
                'label' => 'Account Domain',
                'type' => 'text',
                'description' => 'Enter your account domain from iVvy.',
            ),
        );
    }
}