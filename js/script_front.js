jQuery(document).ready(function($){
    var grades_val= new Array('Awesome!', 'Pretty Good', 'Just Ok', 'Pretty Lame', 'Worthless' );
    
    $('.rate-grade-image a img').mouseover(function(e){
        var elem = $(this);
        e.stopPropagation();
        var src = elem.attr('src');
//        var image = src.match(/\/[^\/]*\.png/);
//       alert(src.replace(/\/[^\/]*\.png/,''));
       var new_image= src.replace('gray','color');
       elem.attr('src', new_image);
       var selVal =$(this).attr('class').match(/\d/);
       $('.grade-text-value').html(grades_val[selVal[0]]);
        
    });
    $('.rate-grade-image a img').mouseout(function(e){
        var elem = $(this);
        e.stopPropagation();
        var src = elem.attr('src');
       var new_image= src.replace('color','gray');

       if($(this).attr('id') != 'user-grade')
            elem.attr('src', new_image);
        $('.grade-text-value').html(null);
        
    });

    
    $('.rate-grade-image a img').click(function(e){
        e.preventDefault();
        var action = 'submit-wpvote';
        var selVal =$(this).attr('class').match(/\d/);
       
//        alert(wpvrSettings.post_id );
      

        
        $.ajax({
            type :  "post",
            url : wpvrSettings.ajaxurl,
            timeout : 5000,
            data : {
                'action' : action,
                'grade-value': selVal[0],
                 'post_id':  wpvrSettings.post_id  
            },
            success :  function(data){
                $('#colophon').html(data);
                   if(data == 'voted' )alert('You have already voted for this post');
                   if(data == 'nv')alert('Your Rating has been saved for this post');
                    if(data == 'updated')alert('Your Rating has been Updated for this post');
                   // window.location.href=window.location.href;
                    
                    }
            })
        })






})
