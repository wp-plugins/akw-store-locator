<?php
//akw-store-locator common functions
 
    $parse_uri = explode('wp-content', __FILE__);
    $wploadAKW = $parse_uri[0].'wp-load.php';
    include_once($wploadAKW);
    //include_once($_SERVER['DOCUMENT_ROOT'].'/nwsite/wp-config.php' );
    //require_once('storeLocatorConfig.php');
    //include_once($_SERVER['DOCUMENT_ROOT'].'/nwsite/wp-load.php' );

    if(isset($_POST['cmd']))
    {
        switch($_POST['cmd'])
        {
            case 'getAddress':
                getAddress();
                break;
            case 'saveAddress':
                saveAddress();
                break;
        }
    }
    
    //Function to get address for records with no geolocation
    function getAddress()
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'Stores';
        $responsea = '';
        
        $sql = "SELECT COUNT(*) FROM $table_name WHERE Latitude=0.000000 AND Longitude=0.000000";
        $count = $wpdb->get_var($sql);
        
        if($count == 0)
        {
          $responsea = "Stores with empty coordinates not found";
        }
        else
        {
            $sql  = "SELECT ID, Street, City, Province, Country, PostalCode\n";
            $sql .= " FROM $table_name WHERE Latitude=0.000000 AND Longitude=0.000000 ORDER BY ID LIMIT 1";
        
            $q = $wpdb->get_results($sql);
            $responsea = "OKAY";
            foreach($q AS $r)
            {
              $responsea .= "\n".$r->ID."|".$r->Street."|".$r->City."|".$r->Province."|".$r->Country."|".$r->PostalCode;
            }
        }
        echo $responsea;
        exit;
    }
    
    //Function to save the geo location for a record
    function saveAddress()
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'Stores';
        
        $wpdb->update(
                      $table_name,
                      array(
                        'Latitude' => $_POST['lat'],
                        'Longitude' => $_POST['long']
                        ),
                      array(
                        'ID' => $_POST['ID']
                      ),
                      array(
                        '%f',
                        '%f'
                      ),
                      array(
                       '%d' 
                      )
                    );
        $response = "OKAY";
        echo $response;
        exit;
    }
    
    //Function to stripslashes and escape string
    function sanitize($data)
    {
        $data = trim($data);
        if (get_magic_quotes_gpc())
        {
            $data = stripslashes($data);
        }
        $data = mysql_real_escape_string($data);
        
        return $data;
    }
    
    //Function for pagination
    function pagination($query, $per_page = 10,$page = 1, $url = '?')
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'Stores';
        
        $query = "SELECT COUNT(*) AS num FROM {$query}";
        
        $total = $wpdb->get_var($query);
        $adjacents = "2";
   
        $page = ($page == 0 ? 1 : $page); 
        $start = ($page - 1) * $per_page;                              
         
        $prev = $page - 1;                         
        $next = $page + 1;
        $lastpage = ceil($total/$per_page);
        $lpm1 = $lastpage - 1;
    
        $pagination = "";
        if($lastpage > 1)
        {  
            $pagination .= "<ul class='pagination'>";
                    $pagination .= "<li class='details'>Page $page of $lastpage</li>";
            if ($lastpage < 7 + ($adjacents * 2))
            {  
                for ($counter = 1; $counter <= $lastpage; $counter++)
                {
                    if ($counter == $page)
                        $pagination.= "<li><a class='current'>$counter</a></li>";
                    else
                        $pagination.= "<li><a href='{$url}pageNo=$counter'>$counter</a></li>";                   
                }
            }
            elseif($lastpage > 5 + ($adjacents * 2))
            {
                if($page < 1 + ($adjacents * 2))    
                {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
                    {
                        if ($counter == $page)
                            $pagination.= "<li><a class='current'>$counter</a></li>";
                        else
                            $pagination.= "<li><a href='{$url}pageNo=$counter'>$counter</a></li>";                   
                    }
                    $pagination.= "<li class='dot'>...</li>";
                    $pagination.= "<li><a href='{$url}pageNo=$lpm1'>$lpm1</a></li>";
                    $pagination.= "<li><a href='{$url}pageNo=$lastpage'>$lastpage</a></li>";     
                }
                elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
                {
                    $pagination.= "<li><a href='{$url}pageNo=1'>1</a></li>";
                    $pagination.= "<li><a href='{$url}pageNo=2'>2</a></li>";
                    $pagination.= "<li class='dot'>...</li>";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
                    {
                        if ($counter == $page)
                            $pagination.= "<li><a class='current'>$counter</a></li>";
                        else
                            $pagination.= "<li><a href='{$url}pageNo=$counter'>$counter</a></li>";                   
                    }
                    $pagination.= "<li class='dot'>..</li>";
                    $pagination.= "<li><a href='{$url}pageNo=$lpm1'>$lpm1</a></li>";
                    $pagination.= "<li><a href='{$url}pageNo=$lastpage'>$lastpage</a></li>";     
                }
                else
                {
                    $pagination.= "<li><a href='{$url}pageNo=1'>1</a></li>";
                    $pagination.= "<li><a href='{$url}pageNo=2'>2</a></li>";
                    $pagination.= "<li class='dot'>..</li>";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
                    {
                        if ($counter == $page)
                            $pagination.= "<li><a class='current'>$counter</a></li>";
                        else
                            $pagination.= "<li><a href='{$url}pageNo=$counter'>$counter</a></li>";                   
                    }
                }
            }
             
            if ($page < $counter - 1){
                $pagination.= "<li><a href='{$url}pageNo=$next'>Next</a></li>";
                $pagination.= "<li><a href='{$url}pageNo=$lastpage'>Last</a></li>";
            }else{
                $pagination.= "<li><a class='current'>Next</a></li>";
                $pagination.= "<li><a class='current'>Last</a></li>";
            }
            $pagination.= "</ul>\n";     
        }
        return $pagination;
    } 
?>