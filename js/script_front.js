jQuery(document).ready(function($){
    
    //test
    $('#get-due').click(function(e){
		country = $('#wb_country').val();        
		mem = $('#mem_type').val()        
            $.ajax({
            type :  "post",
            url : wpvrSettings.ajaxurl,
            timeout : 5000,
            data : {
                'action' : 'get_dues',
                'country':  country,
                'mem_type': mem
            
            },
            success :  function(data){
                $('#mem_output').html(data).hide().fadeIn('slow');             
            }
            
    } )
    
    });
    
    
})
