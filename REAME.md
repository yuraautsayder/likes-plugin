# Likes and Dislikes для WordPress

## Описание

Плагин "Likes and Dislikes" позволяет пользователям ставить лайки и дизлайки на посты вашего сайта WordPress. Это простой и эффективный способ повысить взаимодействие с контентом.

## Установка

    Скачайте плагин:
        Перейдите на страницу плагина в репозитории WordPress и скачайте ZIP-файл.

    Установите плагин:
        Войдите в админ-панель WordPress.
        Перейдите в раздел Плагины > Добавить новый.
        Нажмите на кнопку Загрузить плагин и выберите скачанный ZIP-файл.
        Нажмите Установить и затем Активировать.

    Настройка плагина:
        После активации плагина вы можете настроить его параметры в разделе Настройки > Likes and Dislikes.

## Добавление элементов лайков и дизлайков в шаблон

Чтобы отобразить кнопки лайков и дизлайков на страницах постов, вам нужно добавить следующий код в файл шаблона вашего WordPress-темы (например, single.php или content.php):

```<img src="input-your-icon-plus" alt="" class="like-plus" data-post-id="<?php the_ID(); ?>">
<span class="like-count" id="like-count-<?php the_ID(); ?>">
  <?php
  $likes = get_post_likes_dislikes(get_the_ID());
  echo esc_html($likes['like_result']);
  ?>
</span>
<img src="input-your-icon-minus" alt="" class="like-minus" data-post-id="<?php the_ID(); ?>">
```
