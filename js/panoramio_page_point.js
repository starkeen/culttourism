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
    'columns': 8, 
    'rows': 1, 
    'croppedPhotos':true,
    'disableDefaultEvents':[panoramio.events.EventType.PHOTO_CLICKED]
});
panoramio.events.listen(list_ex_widget, panoramio.events.EventType.PHOTO_CLICKED, function(event) {
    //console.log(event.getPhoto().getPhotoUrl());
    //return false;
});
list_ex_widget.setPosition(0);