<?php

/**
 * Check given value empty or not
 *
 * @param [type] $value
 *
 * @return void
 */
function check_is_empty($value, $default = '')
{
	$value = !empty(esc_attr($value)) ? $value : $default;
	return $value;
}


// WP Adminify function for get an option
if (!function_exists('jltwp_adminify_get_option')) {
	function jltwp_adminify_get_option($option = '', $default = null)
	{
		$options = [];
		// if (is_multisite() && is_site_wide('wp-adminify/wp-adminify.php')) {
		$options = (array) \WPAdminify\Inc\Admin\AdminSettings::get_instance()->get();
		// }
		return (isset($options[$option])) ? $options[$option] : $default;
	}
}

function is_site_wide($plugin)
{
	if (!is_multisite()) {
		return false;
	}

	$plugins = get_site_option('active_sitewide_plugins');
	if (isset($plugins[$plugin])) {
		return true;
	}

	return false;
}




/**
 * Check if the request is coming from an iframe.
 *
 * @param None
 * @throws None
 * @return bool
 */
function is_iframe()
{
    return isset($_SERVER["HTTP_SEC_FETCH_DEST"]) && strtolower($_SERVER["HTTP_SEC_FETCH_DEST"]) === "iframe";
	// $isIframe =  isset($_SERVER["HTTP_SEC_FETCH_DEST"]) && strtolower($_SERVER["HTTP_SEC_FETCH_DEST"]) === "iframe";
    // if ( $isIframe ) return true;
    // if ( isset($_GET['adminify-iframe']) ) return true;
    // return false;
}

/**
 * Load a template file.
 *
 * @param string $template_name The name of the template file to load.
 * @throws None
 * @return void
 */
function adminify_load_template($template_name)
{
	require_once WP_ADMINIFY_PATH . 'Inc/Admin/Frames/Templates/' . $template_name;
}

function maybe_network_admin_url($url) {
    if ( is_network_admin() ) {
        return network_admin_url($url);
    }
    return admin_url($url);
}

/**
 * User Roles Slug from Names
 *
 * @param [type] $user_roles
 *
 * @return void
 */
function jltwp_adminify_menu_roles($user_roles = [])
{
    $disabled_for_roles = [];
    if (!empty( $user_roles)) {
        foreach ($user_roles as $usr_key) {
            $disabled_for_roles[] = strtolower(str_replace(' ', '_', $usr_key));
        }
    }
    return $disabled_for_roles;
}


function jltwp_adminify_build_menu($menu, $submenu, $menu_options) {

    $admin_menu = [];

    foreach ($menu as $key => $item) {

        if (is_array($menu_options)) {
            if (isset($menu_options[$item[2]])) {
                $optiongroup = $menu_options[$item[2]];

                if (!empty($optiongroup['hidden_for'])) {

                    $disabled_for = jltwp_adminify_menu_roles($optiongroup['hidden_for']);

                    if (\WPAdminify\Inc\Utils::restricted_for($disabled_for)) {
                        continue;
                    }
                }
            }
        }


        $menu_slug  = $item[2];
        $menu_title = $item[0];
        $menu_name  = isset($item[5]) ? $item[5] : '';
        $menu_icon  = isset($item[6]) ? $item[6] : '';

        $menu_hook = get_plugin_page_hook($menu_slug, 'admin.php');
        $menu_file = $menu_slug;
        $pos = strpos($menu_file, '?');

        if (false !== $pos) {
            $menu_file = substr($menu_file, 0, $pos);
        }

        $url = '';

        $arrParsedUrl = parse_url($menu_slug);
        if (!empty($arrParsedUrl['scheme'])) {
            if ($arrParsedUrl['scheme'] === "http" || $arrParsedUrl['scheme'] === "https") {
                $url = $menu_slug;
            }
        } else {
            $url = ( ! empty($menu_hook) || ( ( 'index.php' !== $menu_slug ) && file_exists(WP_PLUGIN_DIR . "/$menu_file") && ! file_exists(ABSPATH . "/wp-admin/$menu_file") ) )
                ? maybe_network_admin_url('admin.php?page=' . $menu_slug)
                : maybe_network_admin_url($menu_slug);
        }

        $admin_menu[$menu_slug] = [
            'key'      => $menu_slug,
            'title'    => $menu_title,
            'url'      => $url,
            'name'     => $menu_name,
            'icon'     => $menu_icon,
            'children' => [],
            'separator' => !empty( $item['separator'] ) ? $item['separator'] : ''
        ];

        if (!empty($submenu[$menu_slug])) {
            foreach ($submenu[$menu_slug] as $sub_key => $sub_item) {
                $sub_slug  = $sub_item[2];
                $sub_title = $sub_item[0];
                $sub_name  = isset($sub_item[5]) ? $sub_item[5] : '';
                $sub_icon  = isset($sub_item[6]) ? $sub_item[6] : '';

                $sub_menu_hook = get_plugin_page_hook($sub_slug, $menu_slug);
                $sub_file = $sub_slug;
                $pos = strpos($sub_file, '?');

                if (false !== $pos) {
                    $sub_file = substr($sub_file, 0, $pos);
                }


                $sub_url = '';

                $arrParsedUrl = parse_url($sub_slug);
                if (!empty($arrParsedUrl['scheme'])) {
                    if ($arrParsedUrl['scheme'] === "http" || $arrParsedUrl['scheme'] === "https") {
                        $sub_url = $sub_slug;
                    }
                } else {
                    $sub_url = ( ! empty($sub_menu_hook) || ( ( 'index.php' !== $sub_slug ) && file_exists(WP_PLUGIN_DIR . "/$sub_file") && ! file_exists(ABSPATH . "/wp-admin/$sub_file") ) )
                    ? maybe_network_admin_url('admin.php?page=' . $sub_slug)
                    : maybe_network_admin_url($sub_slug);
                }

                // Wrong Menu/Submenu Links
                // Support for White Label Plugin Url
                if ('white-label' === $sub_slug) {
                    $sub_url = admin_url('options-general.php?page=white-label');
                }

                $admin_menu[$menu_slug]['children'][$sub_slug] = [
                    'key'   => $sub_slug,
                    'title' => $sub_title,
                    'url'   => $sub_url,
                    'name'  => $sub_name,
                    'icon'  => $sub_icon,
                ];
            }
        }
    }
    return $admin_menu;
}
