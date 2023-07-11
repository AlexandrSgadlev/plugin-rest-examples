# Plugin Rest Examples

Это пример использования WP REST API в плагине.
Тут собраны типовые варианты использования REST API. 
Если, Вы только начали разбираться в данном функционале, этот плагин поможет быстрей понять это.



# Полезный код

При желании можно ограничить, какие пользователи на вашем сайте могут использовать функцию паролей приложений. Например, используйте следующий код, чтобы разрешить использование паролей приложений только для администраторов:</br>
<code>add_filter( 'wp_is_application_passwords_available_for_user', 'fix_app_password_availability', 10, 2 );
function fix_app_password_availability( $available, $user ){
&#09;if ( ! user_can( $user, 'manage_options' ) ) {
&#09;&#09;$available = false;
&#09;}
&#09;return $available;
}
</code>

Чтобы полностью отключить пароли приложений:</br>
<code>add_filter( 'wp_is_application_passwords_available', '__return_false' );</code>

Закрывает все маршруты REST API от публичного доступа:</br>
<code>add_filter( 'rest_authentication_errors', function( $result ){
&#09;if( is_null( $result ) && ! current_user_can('edit_others_posts') ){
&#09;&#09;return new WP_Error( 'rest_forbidden', 'You are not currently logged in.', [ 'status'=>401 ] );
&#09;}
&#09;return $result;
} );
</code>
