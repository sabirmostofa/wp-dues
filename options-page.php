<?php
global $wpdb;
$mem_page  = get_permalink(get_option('wb_mem_dues_page_number'));
//Add new membership
if(isset( $_POST['new-submit'] )):
$key = sanitize_title_with_dashes(trim($_POST['name']));
$mem_array = get_option('wp_wb_memberships');

if(!array_key_exists($key, $mem_array));

$mem_array[$key]=array(
	'name' => trim($_POST['name']),
	'low_fee' => 0,
	'low_early' => 0,
	'medium_fee' => 0,
	'medium_early' => 0,
	'high_fee' => 0,
	'high_early' => 0

);

update_option('wp_wb_memberships', $mem_array);



endif;

// db update

if(isset($_POST['update_submit'])){
	$this->update_list($this->data_url);
}


//membeship update
if( isset($_POST['membership-submit'] )):
update_option( 'wp_wb_memberships', $_POST['members'] );

endif;


//if setting submited
if (isset($_POST['earlybird-submit'])):
    $_POST = array_map(create_function('$a', 'return trim($a);'), $_POST);
    update_option('wp_wb_earlybird_date',$_POST['earlybird_date']);

endif;

//set earlybird
$earlybird_date = get_option('wp_wb_earlybird_date');



// If new city submitted
if (isset($_POST['city-submit'])):
    $_POST = array_map(create_function('$a', 'return trim($a);'), $_POST);
    extract($_POST);
    if (strlen($city_name) !== 0 && strlen($city_url) !== 0)        
        if( $this -> not_in_table($city_name) )
        $res = $wpdb->query("insert into $this->table (city_name, city_url) values('$city_name', '$city_url')");
   

endif;





?>

<div class="wrap">
    <form action ='' method='post'>
        <h4>Earlybird Date </h4>
        <input style="width:20%" id='earlybird_date' type='text' name='earlybird_date' value="<?php echo $earlybird_date ?>"/>
        <br/> 
		<br/>
        <input class='button-primary' type='submit' name="earlybird-submit" value='Set Date'/> 
    </form>

    <!-- Form to add a new city and URL -->

    <br/>
    <br/>
    <h4>Memberships and Price</h4> 

    <?php
    
    $memberships = get_option('wp_wb_memberships') ;
    $memberships = $memberships? $memberships: array();
//var_dump($all_cities);
    ?>
   <form method='post' action= ''> 
    <form>
    <table class='widefat' style="text-align:center;width:70%" id="mem_table" >
        <thead>
            <tr>

                <th> Remove</th>
                <th > Membership </th>
                <th colspan="2"> Low Income  </th>
                <th colspan="2"> Lower Middle  </th>
                <th colspan="2"> High+ Upper Middle  </th>
               
            </tr>
            <tr>
            <td></td>
            <td></td>
            <td>Standard</td>
            <td>EarlyBird</td>
            <td>Standard</td>
            <td>EarlyBird</td>
            <td>Standard</td>
            <td>EarlyBird</td>
  
            </tr>
        </thead>
        <tbody>

            <?php
            $drop_image = $this->image_dir . 'b_drop.png';
            foreach($memberships as $key=>$single):
                echo "
                <tr>
                <td><a href='#'> <img id='$key' src='$drop_image' /><a></td>
                 <td> <input type='text' name =\"members[{$key}][name]\" value=\"{$single[name]}\" /></td>
                 <td><input type='text' name =\"members[{$key}][low_fee]\" value=\"{$single[low_fee]}\" /></td>
                 <td><input type='text' name =\"members[{$key}][low_early]\" value=\"{$single[low_early]}\" /></td>
                 <td><input type='text' name =\"members[{$key}][medium_fee]\" value=\"{$single[medium_fee]}\" /></td>
                 <td><input type='text' name =\"members[{$key}][medium_early]\" value=\"{$single[medium_early]}\" /></td>
                 <td><input type='text' name =\"members[{$key}][high_fee]\" value=\"{$single[high_fee]}\" /></td>
                 <td><input type='text' name =\"members[{$key}][high_early]\" value=\"{$single[high_early]}\" /></td>

                 </tr>";
                ?>



                <?php
            endforeach;
                  ?>
        </tbody>

</table>
<br/>
<input type='submit' name='membership-submit' class='button-primary' value="update" />
</form>

<h4>Add a new membership </h4>
<form method='post' action= ''>
<b> Membership Name: </b>
<input type='text' name='name' />
<input type='submit' name='new-submit' class='button-primary' value="Add" />
</form>

<br/>
<b>Membership Dues Calculator page: <a href="<?php echo $mem_page ?>"><?php echo $mem_page ?> </a></b>
<br/>
<br/>
<form action='' method='post'>
<input class='button-primary' type='submit' name ='update_submit' value='update country list'/>
</form>
</div>

<div style="clear:both;width:200px;heigth:20px"></div>
