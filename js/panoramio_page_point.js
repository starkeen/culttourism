var block_width = 500;
var block_columns = 8;
var delta_heights = $('#object_description').height() - $('#map_container').height();
if (delta_heights > 50) {
    block_width = 0.97 * ($('#object_description').width());
    block_columns = 10;
}
else {
    block_width = 0.97 * ($('#object_description').width() - $('#map_container').width());
}
block_width = Math.floor(block_width);
var list_ex_widget = new panoramio.PhotoListWidget('widget_photos', {
    'rect': {
        'sw': {
            'lat': $('#pan_sw_lat').val(), 
            'lng': $('#pan_sw_lon').val()
        }, 
        'ne': {
            'lat': $('#pan_ne_lat').val(), 
            'lng': $('#pan_ne_lon').val()
        }
    }
}, {
    'height': 120,
    'width' : block_width,
    'columns': block_columns, 
    'rows': 1, 
    'croppedPhotos':true,
    'disableDefaultEvents':[panoramio.events.EventType.PHOTO_CLICKED]
});
panoramio.events.listen(list_ex_widget, panoramio.events.EventType.PHOTO_CLICKED, function(event) {
    //console.log(event.getPhoto().getPhotoUrl());
    //return false;
    });
list_ex_widget.setPosition(0);