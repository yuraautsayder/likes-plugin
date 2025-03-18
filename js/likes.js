document.addEventListener('DOMContentLoaded', function () {
	var likeButtons = document.querySelectorAll('.like-plus, .like-minus');

	likeButtons.forEach(function (button) {
		button.addEventListener('click', function () {
			var postId = this.getAttribute('data-post-id');
			var actionType = this.classList.contains('like-plus') ? 'like' : 'dislike';

			var xhr = new XMLHttpRequest();
			xhr.open('POST', likesPlugin.ajax_url, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

			xhr.onreadystatechange = function () {
				if (xhr.readyState === 4) {
					if (xhr.status === 200) {
						var response = JSON.parse(xhr.responseText);

						if (response.success) {
							var likesCount = parseInt(response.data.likes) || 0;
							var dislikesCount = parseInt(response.data.dislikes) || 0;
							var totalCount = likesCount - dislikesCount;

							document.getElementById('like-count-' + postId).textContent = totalCount;
						} else {
							console.log('Error in response data:', response.data);
						}
					} else {
						console.log('AJAX Error:', xhr.statusText);
					}
				}
			};

			var data = 'action=likes_plugin&post_id=' + encodeURIComponent(postId) + '&action_type=' + encodeURIComponent(actionType);
			xhr.send(data);
		});
	});
});
