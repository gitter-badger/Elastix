/**
 * Created by aagafonov on 01.07.14.
 */

function play(el){
    $src = $(el).attr('src');
    $id = $(el).attr('data');

    if ($src == 'modules/dm_custom_reports/images/play.png'){
        console.log('play id = ' + $id);
        // Stop all file
        $('img').each(
            function(i,elem) {
                if(tid = $(elem).attr('data')){
                    aud_elem = document.getElementById(tid);
                    aud_elem.pause();
                    $(elem).attr('src', 'modules/dm_custom_reports/images/play.png');
                };
            }
        )

        $(el).attr('src', 'modules/dm_custom_reports/images/stop.png');

        document.getElementById($id).play();
    } else {
        console.log('stop id = ' + $id);
        document.getElementById($id).pause();
        $(el).attr('src', 'modules/dm_custom_reports/images/play.png');
    }
}
