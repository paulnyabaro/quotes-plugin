jQuery(document).ready(function ($) {
    // Like button click event
    $('.m2m-like').on('click', function () {
        var quoteId = $(this).data('id');
        
        $.ajax({
            type: 'POST',
            url: m2m_ajax.url,
            data: {
                action: 'm2m_handle_like_dislike',
                type: 'like',
                quote_id: quoteId,
                nonce: m2m_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('Liked successfully');
                    location.reload(); // Reload the page to show updated counts
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });

    // Dislike button click event
    $('.m2m-dislike').on('click', function () {
        var quoteId = $(this).data('id');
        
        $.ajax({
            type: 'POST',
            url: m2m_ajax.url,
            data: {
                action: 'm2m_handle_like_dislike',
                type: 'dislike',
                quote_id: quoteId,
                nonce: m2m_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('Disliked successfully');
                    location.reload(); // Reload the page to show updated counts
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
});