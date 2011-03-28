<?
//Include the header
show_header('Ratio Requirements');
?>
<div class="thin">
<h2 class="center">Ratio Rules</h2>
	<div class="box pad">
		<p>
			Your ratio is the amount you've uploaded divided by the amount you've downloaded. 
		</p>
		<p>
			To maintain leeching privileges, we require that you maintain a ratio above a minimum ratio. This is called your "required ratio". If your upload/download ratio goes below your required ratio, your account will be given a two week period to fix it before you lose your ability to download. This is called "ratio watch". 
		</p>
		
		<p>
			The required ratio is <strong>NOT the extra amount of ratio you need to gain</strong>. It is the <strong>minimum</strong> required ratio you must maintain. 
		</p>
		
		<p>
			Your required ratio is unique, and is calculated from the amount you've downloaded, and the percentage of your snatched torrents which you are still seeding. 
		</p>
		
		<p>
			<b>It is not necessary to know how this ratio is calculated. What you need to know is that downloading makes the required ratio go up (bad) and seeding your snatches forever makes your required ratio go down (good). You can view your required ratio in the site header (it is the "Required" value). You want a high ratio, and a low required ratio.</b> 
		</p>
		
		<p>
			The exact formula for calculating the required ratio is provided merely for the curious. It is done in three steps. 
		</p>
		
		<p>
			The first step is by determining how high and how low your required ratio can be. This is determined by looking up how much you've downloaded from the following table:
		</p>
		
		<table>
			<tr class="colhead">
				<td>Amount downloaded</td>
				<td>Required ratio (0% seeded)</td>
				<td>Required ratio (100% seeded)</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] < 5*1024*1024*1024)?'a':'b'?>">
				<td>0-5GB</td>
				<td>0.00</td>
				<td>0.00</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 5*1024*1024*1024 && $LoggedUser['BytesDownloaded'] < 10*1024*1024*1024)?'a':'b'?>">
				<td>5-10GB</td>
				<td>0.15</td>
				<td>0.00</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 10*1024*1024*1024 && $LoggedUser['BytesDownloaded'] < 20*1024*1024*1024)?'a':'b'?>">
				<td>10-20GB</td>
				<td>0.20</td>
				<td>0.00</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 20*1024*1024*1024 && $LoggedUser['BytesDownloaded'] < 30*1024*1024*1024)?'a':'b'?>">
				<td>20-30GB</td>
				<td>0.30</td>
				<td>0.05</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 30*1024*1024*1024 && $LoggedUser['BytesDownloaded'] < 40*1024*1024*1024)?'a':'b'?>">
				<td>30-40GB</td>
				<td>0.40</td>
				<td>0.10</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 40*1024*1024*1024 && $LoggedUser['BytesDownloaded'] < 50*1024*1024*1024)?'a':'b'?>">
				<td>40-50GB</td>
				<td>0.50</td>
				<td>0.20</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 50*1024*1024*1024 && $LoggedUser['BytesDownloaded'] < 60*1024*1024*1024)?'a':'b'?>">
				<td>50-60GB</td>
				<td>0.60</td>
				<td>0.30</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 60*1024*1024*1024 && $LoggedUser['BytesDownloaded'] < 80*1024*1024*1024)?'a':'b'?>">
				<td>60-80GB</td>
				<td>0.60</td>
				<td>0.40</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 80*1024*1024*1024 && $LoggedUser['BytesDownloaded'] < 100*1024*1024*1024)?'a':'b'?>">
				<td>80-100GB</td>
				<td>0.60</td>
				<td>0.50</td>
			</tr>
			<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 100*1024*1024*1024)?'a':'b'?>">
				<td>100+GB</td>
				<td>0.60</td>
				<td>0.60</td>
			</tr>
		</table>
		
		<p>
			So for example, if you've downloaded 25GB, your required ratio will be somewhere between 0.05 and 0.30. 
		</p>
		
		<p>
			To get this range of requirements to a more precise number, what we do is take the required ratio (0% seeded) for your download band, multiply it by <img src="http://chart.apis.google.com/chart?cht=tx&chf=bg,s,FFFFFF00&chl=1%20-%20\frac{Seeding}{Snatched}" alt="(1-(seeding/snatched))" title="(1-(seeding/snatched))" />, and round it up to the required ratio (100% seeded) if need be. Therefore, your required ratio will always lie between the 0% seeded and 100% seeded requirements, depending on the percentage of torrents you are seeding. 
		</p>
		
		<p>
			In the formula, "snatched" is the number of <strong>non-deleted unique snatches</strong> (complete downloads) you have made (so if you snatch a torrent twice, it only counts once, and if it is then deleted, it's not counted at all). "Seeding" is the average number of torrents you've seeded over at least 72 hours in the past week. If you've seeded less than 72 hours in the past week, the "seeding" value will go down (which is bad). 
		</p>
		
		<p>
			Thus, if you have downloaded less than 20GB, and you are seeding 100% of your snatches, you will have <strong>no required ratio</strong>. If you have downloaded less than 5GB, then no matter what percentage of snatches you are seeding, you will again have no required ratio. 
		</p>
		
		<p>
			If you stop seeding for an entire week, your required ratio will be the "required ratio (0% seeded)" for your download band. Your required ratio will go down once you start seeding again. 
		</p>
		
		<p>
			Take note how, as your download increases, the <strong>0% seeded and 100% seeded required ratios begin to taper together</strong>. They meet at 100GB of download, meaning that after you've downloaded 100GB, your ratio requirement will be 0.60, no matter what percentage of your snatches you're seeding. 
		</p>
		
		<h3>Important information you should know</h3>
		
		<p>
			If your ratio does not meet your required ratio, you will be put on ratio watch. You will have <strong>two weeks</strong> to get your ratio above your required ratio - <strong>failure to do so will result in your downloading privileges being automatically disabled</strong>. 
		</p>
		
		<p>
			If you download over 10GB while you're on ratio watch, you will be instantly disabled. 
		</p>
		
		<p>
			Everyone gets to download their first 5GB before ratio watch kicks in.
		</p>
		
		<p>
			<b>To get out of ratio watch, you must either raise your ratio by uploading more, or lower your required ratio by seeding more. Your ratio MUST be above your required ratio.</b>
		</p>
		
		<p>
			If you have lost your downloading privileges, your new required ratio will be the 0% seeded ratio. You will re-gain your downloading privileges once your ratio is above that required ratio. 
		</p>
		
		<p>
			The ratio watch system is completely automatic, and cannot be altered by staff. 
		</p>

	</div>
<? include('jump.php'); ?>
</div>
<?
show_footer();
?>
