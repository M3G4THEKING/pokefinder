<?php
global $clock;
global $conn;
global $assetRepo;
global $mapkey;
$mon_name = json_decode(file_get_contents('https://raw.githubusercontent.com/cecpk/OSM-Rocketmap/master/static/data/pokemon.json'), true);
$dex = json_decode(file_get_contents('https://raw.githubusercontent.com/KartulUdus/Professor-Poracle/master/src/util/description.json'), true);
$stats = json_decode(file_get_contents('https://raw.githubusercontent.com/KartulUdus/PoracleJS/v4/src/util/monsters.json'), true);
$released = json_decode(file_get_contents('https://pogoapi.net/api/v1/released_pokemon.json'), true);
$shiny = json_decode(file_get_contents('https://pogoapi.net/api/v1/shiny_pokemon.json'), true);
$forms = json_decode(file_get_contents('https://raw.githubusercontent.com/darkelement1987/PoracleJS/patch-5/src/util/forms.json'), true);

if(isset($_GET['pokemon'])){
$pokemon = $_GET['pokemon'];
$gen = 0;

// Detect amount of forms seen for mon
$cfquery = 'select distinct form from pokemon where pokemon_id=' . $pokemon;
$result = $conn->query($cfquery);
$formsseen = '';
if($result){$cfcount = $result->num_rows;}

// Detect shiny and show/hide
if(empty($shiny[$pokemon])){
    $showshiny='false';
    } else {
    if (
!empty($shiny[$pokemon]) || $shiny[$pokemon]['found_egg'] == 'true' || 
$shiny[$pokemon]['found_evolution'] == 'true' || 
$shiny[$pokemon]['found_raid'] == 'true' || 
$shiny[$pokemon]['found_research'] == 'true' ||
$shiny[$pokemon]['found_wild'] == 'true'){
    $isshiny='http://raw.githubusercontent.com/darkelement1987/shinyassets/master/96x96/pokemon_icon_' . str_pad($pokemon, 3, 0, STR_PAD_LEFT) . '_00_shiny.png';
    $showshiny='true';
    } else {
    $showshiny='false';
    }
}

switch ($pokemon) {
    case $pokemon <= 151:
    $gen = 1;
    break;
    case $pokemon <= 251:
    $gen = 2;
    break;
    case $pokemon <= 386:
    $gen = 3;
    break;
    case $pokemon <= 493:
    $gen = 4;
    break;
    case $pokemon <= 649:
    $gen = 5;
    break;
    case $pokemon <= 721:
    $gen = 6;
    break;
    case $pokemon <= 809:
    $gen = 7;
    break;
}

if(!empty($released[$pokemon])){$checkrelease = $released[$pokemon];} else {$checkrelease = '';}
if(empty($pokemon) || !is_numeric($pokemon) || $pokemon < 1 || $pokemon > 809 ){echo 'NO VALID/EMPTY ID';} else {

// Use formid 0 for images if &form= is not used
if(isset($_GET['form'])){$form=$_GET['form'];} else {$form="0";}
if(!empty($forms[$pokemon][$form])){
if(!empty($forms[$pokemon][$form])){$formname = str_replace("_"," ",$forms[$pokemon][$form]);} else {$formname="";}} else {$formname="Unknown form";}

// Check total mons for spawnrate calculation
$totalquery = $conn->query("select (select count(raid.pokemon_id) from raid) + (select count(pokemon.pokemon_id) from pokemon) as total");
$totalrow = $totalquery->fetch_assoc();
$totalquery->close();

// Mon Ranking

$monrankquery = $conn->query("SELECT pokemon_id, count, rank FROM ( SELECT pokemon_id, count, row_number() OVER ( ORDER BY COUNT DESC, pokemon_id ASC) AS rank FROM ( SELECT pokemon_id, COUNT(pokemon_id) AS COUNT FROM pokemon GROUP BY pokemon_id ) a ) b WHERE pokemon_id=" . $pokemon . " ORDER BY rank asc, pokemon_id asc");
$monrankrow = $monrankquery->fetch_assoc();
$monrankquery->close();
if(!empty($monrankrow['rank'])){
    $monrank='<a href="index.php?page=rank&mode=pokemon">#' . $monrankrow['rank'] . '</a>';
    } else {
        $monrank='-';
        }

// Raid Mon Ranking

$raidrankquery = $conn->query("SELECT pokemon_id, count, rank FROM ( SELECT pokemon_id, count, row_number() OVER ( ORDER BY COUNT DESC, pokemon_id ASC) AS rank FROM ( SELECT pokemon_id, COUNT(pokemon_id) AS COUNT FROM raid GROUP BY pokemon_id ) a ) b WHERE pokemon_id=" . $pokemon . " ORDER BY rank asc, pokemon_id asc");
$raidrankrow = $raidrankquery->fetch_assoc();
$raidrankquery->close();
if(!empty($raidrankrow['rank'])){
    $raidrank='<a href="index.php?page=rank&mode=raid">#' . $raidrankrow['rank'] . '</a>';
    } else {
        $raidrank='-';
        }

if(!isset($_GET['form'])){
$monquery = $conn->query("select pokemon_id as pid, (select count(pokemon_id) from pokemon where pokemon_id=" . $pokemon . ") as count, UNIX_TIMESTAMP(CONVERT_TZ(last_modified, '+00:00', @@global.time_zone)) as last_seen, latitude, longitude from pokemon where pokemon_id=" . $pokemon . " order by last_seen desc limit 1");
$monrow = $monquery->fetch_assoc();
$monquery->close();
$monname = $mon_name[$pokemon]['name'];

$raidmonquery = $conn->query("select pokemon_id as pid, (select count(pokemon_id) from raid where pokemon_id=" . $pokemon . ") as count, UNIX_TIMESTAMP(CONVERT_TZ(last_scanned, '+00:00', @@global.time_zone)) as last_seen from raid where pokemon_id=" . $pokemon . " order by last_seen desc limit 1");
$raidmonrow = $raidmonquery->fetch_assoc();
$raidmonquery->close();
$raidmonname = $mon_name[$pokemon]['name'];
} else {
    $monquery = $conn->query("select pokemon_id as pid, (select count(pokemon_id) from pokemon where pokemon_id=" . $pokemon . " and form=" . $form . ") as count, UNIX_TIMESTAMP(CONVERT_TZ(last_modified, '+00:00', @@global.time_zone)) as last_seen, latitude, longitude from pokemon where pokemon_id=" . $pokemon . " and form=" . $form . " order by last_seen desc limit 1");
    $monrow = $monquery->fetch_assoc();
    $monquery->close();
    if ($form == '0'){
    $monname = $mon_name[$pokemon]['name'];
    } else {
        $monname = $mon_name[$pokemon]['name'] . ' (' .  $formname . ')';
    }
    
    $raidmonquery = $conn->query("select pokemon_id as pid, (select count(pokemon_id) from raid where pokemon_id=" . $pokemon . " and form=" . $form . ") as count, UNIX_TIMESTAMP(CONVERT_TZ(last_scanned, '+00:00', @@global.time_zone)) as last_seen from raid where pokemon_id=" . $pokemon . " and form=" . $form . " order by last_seen desc limit 1");
    $raidmonrow = $raidmonquery->fetch_assoc();
    $raidmonquery->close();
    if ($form == '0'){
    $raidmonname = $mon_name[$pokemon]['name'];
    } else {
        $raidmonname = $mon_name[$pokemon]['name'] . ' (' .  $formname . ')';
    }}

$monseen = $monrow['count'];
$raidmonseen = $raidmonrow['count'];
$totalseen = $monseen + $raidmonseen;
$total = $totalrow['total'];
$spawnrate = number_format((($totalseen / $total)*100), 2, '.', '');
$lat = $monrow['latitude'];
$lon = $monrow['longitude'];
$last = $monrow['last_seen'];
$raidlast = $raidmonrow['last_seen'];

$highestquery = $conn->query("select pokemon_id, ROUND(((individual_attack+individual_defense+individual_stamina)/45)*100,1) as iv from pokemon where pokemon_id=" . $pokemon . " and form=" . $form . " order by ROUND(((individual_attack+individual_defense+individual_stamina)/45)*100,1) desc limit 1");
$highestrow = $highestquery->fetch_assoc();
$highestquery->close();
if(!empty($highestrow['iv'])){$highest = $highestrow['iv'] . '%';} else {$highest = '-';}

$maxcpquery = $conn->query("select pokemon_id, cp from pokemon where pokemon_id=" . $pokemon . " and form=" . $form . " order by cp desc limit 1");
$maxcprow = $maxcpquery->fetch_assoc();
$maxcpquery->close();
if(!empty($maxcprow['cp'])){$maxcp = $maxcprow['cp'];} else {$maxcp = '-';}

// Rarity calculation

if($totalseen>0){
$rarity = 'Common';

switch ($rarity) {
    case $spawnrate == 0.00:
    $rarity = 'New Spawn';
        break;
    case $spawnrate < 0.01:
    $rarity = 'Ultra Rare';
        break;
    case $spawnrate < 0.03:
    $rarity = 'Very Rare';
        break;
    case $spawnrate < 0.5:
    $rarity = 'Rare';
        break;
    case $spawnrate < 0.1:
    $rarity = 'Uncommon';
        break;
}
} else {
    $rarity = 'No rarity, try a <a href="#forms">form</a>';
}

if(!$monrow || empty($monrow)){$monseen='0';$last='-';} else {$last = date('l jS \of F Y ' . $clock, $last);}
if(!$raidmonrow || empty($raidmonrow)){$raidmonseen='0';$raidlast='-';} else {$raidlast = date('l jS \of F Y ' . $clock, $raidlast);}

$img = $assetRepo . 'pokemon_icon_' . str_pad($pokemon, 3, 0, STR_PAD_LEFT) . '_' . str_pad($form, 2, 0, STR_PAD_LEFT) . '.png';

// Perform check because of missing 719 - 807 in json
if($pokemon < 808){
if(empty($dex[$pokemon-1]['description'])){$desc='No description available';} else { $desc = $dex[$pokemon-1]['description']; }
if(empty($stats[str_pad($pokemon, 1, 0, STR_PAD_LEFT) . '_' . str_pad($form, 1, 0, STR_PAD_LEFT)]['types'][0]['name'])){$type1='Unknown type(s)';} else { $type1 = $stats[str_pad($pokemon, 1, 0, STR_PAD_LEFT) . '_' . str_pad($form, 1, 0, STR_PAD_LEFT)]['types'][0]['emoji']. $stats[str_pad($pokemon, 1, 0, STR_PAD_LEFT) . '_' . str_pad($form, 1, 0, STR_PAD_LEFT)]['types'][0]['name']; }
if(empty($stats[str_pad($pokemon, 1, 0, STR_PAD_LEFT) . '_' . str_pad($form, 1, 0, STR_PAD_LEFT)]['types'][1]['name'])){$type2='';} else { $type2 = ' / ' . $stats[str_pad($pokemon, 1, 0, STR_PAD_LEFT) . '_' . str_pad($form, 1, 0, STR_PAD_LEFT)]['types'][1]['emoji'] . $stats[str_pad($pokemon, 1, 0, STR_PAD_LEFT) . '_' . str_pad($form, 1, 0, STR_PAD_LEFT)]['types'][1]['name']; }
} else {
    if(empty($dex[$pokemon-90]['description'])){$desc='No description available';} else { $desc = $dex[$pokemon-90]['description']; }
if(empty($dex[$pokemon-90]['types'][0])){$type1='Unknown type(s)';} else { $type1 = ucfirst($dex[$pokemon-90]['types'][0]); }
if(empty($dex[$pokemon-90]['types'][1])){$type2='';} else { $type2 = ' / '. ucfirst($dex[$pokemon-90]['types'][1]); }
}

if(empty($checkrelease)){echo '<div class="alert alert-danger" role="alert">Pokémon is not in game yet</div>';}

// Replace mon image with placeholder sprite if image is not in assets yet
if(!file_exists($img)){
    $img='https://raw.githubusercontent.com/ZeChrales/PogoAssets/master/pokemon_icons/pokemon_icon_000.png';
    }
?>

<div class="container">
<div class="jumbotron-fluid">
<div class="media">
  <img src="<?=$img?>" class="dexentry">
  <div class="media-body">
    <h3 class="mt-0"><?=$monname?></h3>
    <h5><small>📱 Pokedex ID: #<?=str_pad($pokemon, 3, 0, STR_PAD_LEFT)?><br><?=$type1 . $type2 . ' / Gen ' . $gen . ' / ' . $rarity?></small></h5>
  </div>
</div>
<hr class="my-4">
<h4 class="display-6">Description</h4>
  <p class="lead"><?= $desc?></p>
<hr class="my-4">

<h4 class="display-6">Most Seen Rank</h4>
  <p class="lead">
  
<div class="table-responsive-sm">
<table id="seenTable" class="table table-striped table-bordered w-auto">
<tbody>

<tr>
<th>Wild:</th>
<td><?= $monrank?></td>
</tr>

<tr>
<th>Raid:</th>
<td><?= $raidrank?></td>
</tr>

</tbody>
</table>
</div>
  </p>
  <hr class="my-4">
  <?php if($form > 0){?>
<h4 class="display-6">Stats<?php if($cfcount > 1){if(!isset($_GET['form'])){echo ' for all forms';} else {echo ' for form "' . $formname . '"';}} ?></h4>
  <?php } else {?>
  <h4 class="display-6">Stats</h4>
  <?php }?>
<p class="lead">
<?php if($totalseen>0){?>

<div class="table-responsive-sm">
<table id="seenTable" class="table table-striped table-bordered w-auto">
<tbody>

<tr>
<th>Total seen:</th>
<td><?= $totalseen?></td>
</tr>

<tr>
<th><?= $cfcount?> Form(s) seen:</th>
<td> 
<?php if($result && $result->num_rows >= 1 ){
    while ($row = $result->fetch_object() ) {
        $formsseenimg = $assetRepo . 'pokemon_icon_' . str_pad($pokemon, 3, 0, STR_PAD_LEFT) . '_' . str_pad($row->form, 2, 0, STR_PAD_LEFT) . '.png';
        if(!file_exists($formsseenimg)){
            $formsseenimg='https://raw.githubusercontent.com/ZeChrales/PogoAssets/master/pokemon_icons/pokemon_icon_000.png';
            }
            if ($row->form == '0'){
                $output = 'No Form';
                } else {if(!empty($forms[$pokemon][$row->form])){$output = $forms[$pokemon][$row->form];} else {$output = 'Unknown form';}}
                echo '<img src="' . $formsseenimg . '" height="48" width="48"> - <a href="index.php?page=seen&pokemon=' . $pokemon . '&form=' . $row->form . '">' . $output . '</a><br>';
                }
                }?>
</td>
</tr>

<tr>
<th>Wild:</th>
<td><?= $monseen?></td>
</tr>

<tr>
<th>Raids:</th>
<td><?= $raidmonseen?></td>
</tr>

<tr>
<th>Recently wild:</th>
<td><?= $last?></td>
</tr>

<tr>
<th>Recently raids:</th>
<td><?= $raidlast?></td>
</tr>

<tr>
<th>Spawnrate:</th>
<td><?= $spawnrate?>%</td>
</tr>

<tr>
<th>Max IV seen:</th>
<td><?= $highest?></td>
</tr>

<tr>
<th>Max CP seen:</th>
<td><?= $maxcp?></td>
</tr>

</tbody>
</table>
</div>
<?php } else { echo 'This form is not seen';}?>
<hr class="my-4">
<span id='forms'><h4 class="display-6">Forms</h4></span>
<?php
$data = file_get_contents('https://raw.githubusercontent.com/darkelement1987/PoracleJS/patch-5/src/util/forms.json');
$json = json_decode($data,true);
$i=0;?>
<div class="table-responsive-sm">
<table id="formTable" class="table table-striped table-bordered w-auto">
  <thead>
    <tr>
      <th>Pic</th>
      <th>Form</th>
      <th>Pokedex</th>
    </tr>
  </thead>
  <tbody>
  <?php if ($showshiny=='true'){?>
      <tr align='center' style='align-content:center;text-align:center;'>
      <td class="align-middle"><img src='<?=$isshiny?>' height='96' width='96' class='dexentry'></td>
      <td class="align-middle">Shiny</td>
      <td class="align-middle"><a href="#">Link</a></td>
    </tr>
  <?php } else {}
  $noformimg=$assetRepo . 'pokemon_icon_' . str_pad($pokemon, 3, 0, STR_PAD_LEFT) . '_00.png';
  if(!file_exists($noformimg)){
            $noformimg='https://raw.githubusercontent.com/ZeChrales/PogoAssets/master/pokemon_icons/pokemon_icon_000.png';
            }?>
  <tr align='center' style='align-content:center;text-align:center;'>
  <td class="align-middle"><img src='<?=$noformimg?>'height='96' width='96' class='dexentry'></td>
  <td class="align-middle">No form</td>
  <td class="align-middle"><a href="#">Link</a></td>
  </tr>
<?php foreach($json as $pokemonid => $value) if ($pokemonid == $pokemon){
    foreach ($value as $formid => $fname) if ($fname != 'Shadow' && $fname != 'Purified' && $fname != 'NoEvolve'){
        $formimg = 'images/pokemon/pokemon_icon_' . str_pad($pokemonid, 3, 0, STR_PAD_LEFT) . '_' . str_pad($formid, 2, 0, STR_PAD_LEFT) . '.png';
        $link = 'index.php?page=seen&pokemon=' . $pokemon . '&form=' . $formid;
        if(!file_exists($formimg)){
            $formimg='https://raw.githubusercontent.com/ZeChrales/PogoAssets/master/pokemon_icons/pokemon_icon_000.png';
            }
        
        ?>
    <tr align='center' style='align-content:center;text-align:center;'>
      <td class="align-middle"><img src='<?=$formimg?>' height='96' width='96' class='dexentry'></td>
      <td class="align-middle"><?=str_replace("_"," ",$fname);?></td>
      <td class="align-middle"><a href="<?=$link?>">Link</a></td>
    </tr>
<?php }}?>
  </tbody>
</table>
</div>

<hr class="my-4">
<h4 class="display-6">Evolutions</h4>
<?php
$data = file_get_contents('https://raw.githubusercontent.com/KartulUdus/Professor-Poracle/master/src/util/description.json');
$json = json_decode($data);

$nametoid = json_decode(file_get_contents('json/namedex.json'), true);

?>

<div class="table-responsive-sm">
<table id="evoTable" class="table table-striped table-bordered w-auto">
  <thead>
    <tr>
      <th>Evolution</th>
      <th>Pokedex</th>
    </tr>
  </thead>
  <tbody>
  <?php
  foreach($json as $entry) {
      if($entry->pkdx_id == $pokemon){
          foreach ($entry->evolutions as $evo) {
              $evoname=$evo->to;
              if(empty($nametoid[$evoname])){$evoid='0';} else {$evoid = $nametoid[$evoname]['id'];}
              $evoimg='images/pokemon/pokemon_icon_' . str_pad($evoid, 3, 0, STR_PAD_LEFT) . '_00.png';
              if(!file_exists($evoimg)){
                  $evoimg='https://raw.githubusercontent.com/ZeChrales/PogoAssets/master/pokemon_icons/pokemon_icon_000.png';
                  }
              ?>
              <tr align='center' style='align-content:center;text-align:center;'>
              <td class="align-middle"><img src="<?=$evoimg?>" class="dexentry"><br><?=$evo->to?></td>
              <td class="align-middle"><a href="index.php?page=seen&pokemon=<?=$evoid?>">Link</td>
              </tr>
              <?php }
      }
  }
      ?>
  </tbody>
  </table>
  </div>

<hr class="my-4">
<?php if(!empty($mapkey)){
    if($totalseen>0){?>
<h4 class="display-6">Location last seen</h4>
<a href="https://maps.google.com/?q=<?=$lat. ',' .$lon?>"><img src="https://open.mapquestapi.com/staticmap/v5/map?locations=<?=$lat. ',' .$lon?>&key=<?=$mapkey?>&zoom=15&size=250,200" style="border: 1px solid grey;" class="minimap"></a><br>
<hr class="my-4">
    <?php }}?>
[ <a href="index.php?page=pokedex">Return to pokedex</a> ]<?php if($pokemon >1){?>[ <a href="index.php?page=seen&pokemon=<?=(($pokemon)-1)?>">Previous</a> ]<?php }?><?php if($pokemon <809){?>[ <a href="index.php?page=seen&pokemon=<?=(($pokemon)+1)?>">Next</a> ]<?php }?>
</p>
</div>
</div>

<?php }} else {echo 'NO ID GIVEN';}?>
