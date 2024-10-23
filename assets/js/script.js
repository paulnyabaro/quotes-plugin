jQuery(document).ready(function($) {
    $('.m2m-like, .m2m-dislike').on('click', function() {
        var post_id = $(this).data('id');
        var type = $(this).hasClass('m2m-like') ? 'like' : 'dislike';

        $.ajax({
            url: m2m_ajax.url,
            type: 'post',
            data: {
                action: 'm2m_like_dislike',
                post_id: post_id,
                type: type,
                security: m2m_ajax.nonce
            },
            success: function(response) {
                if (type === 'like') {
                    $('.m2m-like[data-id="' + post_id + '"]').text('Like (' + response.data.likes + ')');
                } else {
                    $('.m2m-dislike[data-id="' + post_id + '"]').text('Dislike (' + response.data.dislikes + ')');
                }
            }
        });
    });

    $('.m2m-share-btn').on('click', function() {
        var url = window.location.href;
        alert('Share this quote: ' + url);
    });
});
