$(document).ready(function () {
    $("#ws-token-refresh").on("click", function () {
        document.location.href = 'https://oauth.yandex.ru/authorize?response_type=code&client_id='
                + $(this).data('appid');
    });
});