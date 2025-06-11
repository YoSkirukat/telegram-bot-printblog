jQuery(document).ready(function($) {
    $('#telegram-login a').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        window.open(url, '_blank', 'width=600,height=400');
    });

    // Check if we have a chat_id in the URL
    var urlParams = new URLSearchParams(window.location.search);
    var chatId = urlParams.get('chat_id');
    if (chatId) {
        $.ajax({
            url: tbaAuth.rest_url,
            method: 'POST',
            data: JSON.stringify({ chat_id: chatId }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    window.location.href = window.location.pathname;
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    }
}); 