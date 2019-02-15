<?php namespace AliasProject\WPRoutes;

class Routes
{
    function __construct()
    {
        add_action('do_parse_request', function($do_parse, $wp) {
            $routes = [];
            $current_url = $this->get_current_url();
            $routes = apply_filters('routing_add_routes', $routes, $current_url);
            $urlParts = explode('?', $current_url, 2);
            $urlPath = trim($urlParts[0], '/');
            $urlVars = [];

            if (empty($routes) || !is_array($routes) ) {
                return $do_parse;
            }

            if (isset($urlParts[1])) {
                parse_str($urlParts[1], $urlVars);
            }

            $query_vars = null;
            foreach($routes as $pattern => $callback) {
                if (preg_match('~' . trim($pattern, '/') . '~', $urlPath, $matches)) {
                    $routeVars = $callback($matches);

                    if (is_array($routeVars)) {
                        $query_vars = array_merge($routeVars, $urlVars);
                        break;
                    }
                }
            }

            if (is_array($query_vars)) {
                $wp->query_vars = $query_vars;
                do_action('routing_matched_vars', $query_vars);

                return false;
            }

            return $do_parse;
        }, 30, 2);

        add_action('routing_matched_vars', function() {
            remove_action('template_redirect', 'redirect_canonical');
        }, 30);
    }

    function get_current_url()
    {
        $current_url = trim(esc_url_raw(add_query_arg([])), '/');
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');

        if ($home_path && strpos($current_url, $home_path) === 0) {
            $current_url = trim(substr($current_url, strlen($home_path)), '/');
        }

        return $current_url;
    }

    function add_frontend_route($pattern, callable $callback) {
        add_filter('routing_add_routes', function($routes) use($pattern, $callback) {
            $routes[$pattern] = $callback;
            return $routes;
        });
    }
}
