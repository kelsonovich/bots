<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<div class="test">

</div>

<script>
    $(() => {
        $.ajax({
            url: 'https://mozgva.com/teams/list?game_id=5387,
            type: 'get',
            dataType: 'script',
            success: function(data) {
                $('.test').html(data)
            }
        });
    })
</script>