jQuery(function( $ )
{

    var link = $('#rightmenu-showhide'), timeout = null;

    link.attr('href', 'JavaScript:void(0);').html( link.width() <= 22 ? '&lt;' : '&gt;' ).click(function()
    {
        if ( timeout !== null )
        {
            clearTimeout( timeout );
            timeout = null;
        }
        var link = $( this ), linkbox = $( '#rightmenu' ), hidden = linkbox.width() < 22;
        if ( hidden )
        {
            $('#maincontent').css( 'marginRight', '13em' );
            linkbox.animate({
                width: '13em'
            }, 650, 'swing', function(){
                timeout = setTimeout( saveRightMenuStatus, 500 );
            } );
        }
        else
        {
            linkbox.animate({
                width: '1em'
            }, 650, 'swing', function(){
                $('#maincontent').css( 'marginRight', '1em' );
                timeout = setTimeout( saveRightMenuStatus, 500 );
            } );
        }
        link.html( hidden ? '&gt;' : '&lt;' );
    });
    function saveRightMenuStatus()
    {
        var show  = $( '#rightmenu' ).width() < 22 ? '' : '1';
        $.post( $.ez.url.replace( 'ezjscore/', 'user/preferences/set_and_exit/admin_right_menu_show/' ) + show );
    }
});