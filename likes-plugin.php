<?php

/**
 * Plugin Name: Likes Plugin
 * Description: Plugin for added likes and dislikes
 * Version: 1.0
 * Author: work@fluddev.ru
 */

if (!defined("ABSPATH")) {
  exit();
}
function likes_plugin_enqueue_scripts()
{
  wp_enqueue_script(
    "likes-plugin-script",
    plugins_url('js/likes.js', __FILE__),
    true
  );
  wp_localize_script("likes-plugin-script", "likesPlugin", [
    "ajax_url" => admin_url("admin-ajax.php"),
  ]);
}
add_action("wp_enqueue_scripts", "likes_plugin_enqueue_scripts");

function likes_plugin_create_table()
{
  global $wpdb;

  $table_name = $wpdb->prefix . "likes";
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id mediumint(9) NOT NULL,
        user_ip varchar(100) NOT NULL,
        page_url varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        is_like tinyint(1) NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

  require_once ABSPATH . "wp-admin/includes/upgrade.php";
  dbDelta($sql);
}
register_activation_hook(__FILE__, "likes_plugin_create_table");


function likes_plugin_handle_ajax()
{
  global $wpdb;

  if (!isset($_POST["post_id"]) || !isset($_POST["action_type"])) {
    wp_send_json_error("Недостаточно данных");
    wp_die();
  }

  $post_id = intval($_POST["post_id"]);
  $action_type = sanitize_text_field($_POST["action_type"]);
  $user_ip = $_SERVER["REMOTE_ADDR"];
  $page_url = esc_url($_SERVER["HTTP_REFERER"]);

  $existing_vote = $wpdb->get_row(
    $wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}likes WHERE post_id = %d AND user_ip = %s",
      $post_id,
      $user_ip
    )
  );

  if ($existing_vote) {
    if ($action_type === "like") {
      $wpdb->update(
        "{$wpdb->prefix}likes",
        ["is_like" => 1],
        ["id" => $existing_vote->id]
      );
    } elseif ($action_type === "dislike") {
      $wpdb->update(
        "{$wpdb->prefix}likes",
        ["is_like" => 0],
        ["id" => $existing_vote->id]
      );
    }
  } else {
    $wpdb->insert("{$wpdb->prefix}likes", [
      "post_id" => $post_id,
      "user_ip" => $user_ip,
      "page_url" => $page_url,
      "is_like" => $action_type === "like" ? 1 : 0,
    ]);
  }

  $likes_count = $wpdb->get_var(
    $wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->prefix}likes WHERE post_id = %d AND is_like = 1",
      $post_id
    )
  );
  $dislikes_count = $wpdb->get_var(
    $wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->prefix}likes WHERE post_id = %d AND is_like = 0",
      $post_id
    )
  );

  wp_send_json_success([
    "likes" => $likes_count,
    "dislikes" => $dislikes_count,
  ]);
  wp_die();
}

add_action("wp_ajax_likes_plugin", "likes_plugin_handle_ajax");
add_action("wp_ajax_nopriv_likes_plugin", "likes_plugin_handle_ajax");

function get_post_likes($post_id)
{
  return (int) get_post_meta($post_id, 'likes_count', true);
}

function likes_plugin_render_admin_page()
{
?>
  <div class="wrap">
    <h1>Статистика Лайков</h1>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th>Запись</th>
          <th>Количество Лайков</th>
          <th>Количество Дизлайков</th>
        </tr>
      </thead>
      <tbody>
        <?php
        global $wpdb;
        $results = $wpdb->get_results(
          "SELECT post_id, SUM(CASE WHEN is_like = 1 THEN 1 ELSE 0 END) as likes, SUM(CASE WHEN is_like = 0 THEN 1 ELSE 0 END) as dislikes FROM {$wpdb->prefix}likes GROUP BY post_id"
        );

        foreach ($results as $row) {
          // Получаем название записи
          $post_title = get_the_title($row->post_id);
          $detail_url = admin_url('admin.php?page=likes_detail&post_id=' . $row->post_id);

          echo "<tr>";
          echo "<td><a href='{$detail_url}'>" . esc_html($post_title) . "</a></td>";
          echo "<td>" . esc_html($row->likes) . "</td>";
          echo "<td>" . esc_html($row->dislikes) . "</td>";
          echo "</tr>";
        } ?>
      </tbody>
    </table>
  </div>
<?php
}

function likes_plugin_render_detail_page()
{
  if (!isset($_GET['post_id'])) {
    return;
  }

  $post_id = intval($_GET['post_id']);
?>
  <div class="wrap">
    <h1>Статистика Лайков для записи: <?php echo esc_html(get_the_title($post_id)); ?></h1>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th>IP пользователя</th>
          <th>Адрес страницы</th>
          <th>Время</th>
        </tr>
      </thead>
      <tbody>
        <?php
        global $wpdb;
        $results = $wpdb->get_results(
          $wpdb->prepare("SELECT user_ip, page_url, created_at FROM {$wpdb->prefix}likes WHERE post_id = %d", $post_id)
        );

        foreach ($results as $row) {
          echo "<tr>";
          echo "<td>" . esc_html($row->user_ip) . "</td>";
          echo "<td>" . esc_html($row->page_url) . "</td>";
          echo "<td>" . esc_html($row->created_at) . "</td>";
          echo "</tr>";
        } ?>
      </tbody>
    </table>
  </div>
<?php
}

add_action('admin_menu', function () {
  add_menu_page('Статистика Лайков', 'Статистика Лайков', 'manage_options', 'likes_plugin', 'likes_plugin_render_admin_page');
  add_submenu_page('likes_plugin', 'Детальная статистика', 'Детальная статистика', 'manage_options', 'likes_detail', 'likes_plugin_render_detail_page');
});


function get_post_likes_dislikes($post_id)
{
  global $wpdb;

  $result = $wpdb->get_row(
    $wpdb->prepare("
            SELECT
                SUM(CASE WHEN is_like = 1 THEN 1 ELSE 0 END) AS likes,
                SUM(CASE WHEN is_like = 0 THEN 1 ELSE 0 END) AS dislikes
            FROM {$wpdb->prefix}likes
            WHERE post_id = %d
        ", $post_id)
  );

  $like_result = (isset($result->likes) ? (int)$result->likes : 0) - (isset($result->dislikes) ? (int)$result->dislikes : 0);

  return [
    'likes' => isset($result->likes) ? (int)$result->likes : 0,
    'dislikes' => isset($result->dislikes) ? (int)$result->dislikes : 0,
    'like_result' => $like_result
  ];
}
