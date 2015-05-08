<div class="loginScreen">
	<div class="mod-box">
	<?php 
		echo $this->Form->create('JobQueue',array('url'=>$this->request->here)); 
	?>
		<div class="username-field mbottom-10 mtop-10">
			<span class="input-default">USER</span>
			<?php echo $this->Form->input('username',array('class'=>'width-285 text mtop-10 default-val','label'=>false))?>
		</div>
		
		<div class="password-field">
			<span class="input-default">PASSWORD</span>
			<?php echo $this->Form->input('password',array('class'=>'width-285 text default-val', 'label'=>false))?>
		</div>
		
		<div class="login-buttons float-right">
			<input type="submit" name="submit" value="Login" class="submit-button mbottom-5 mtop-10 mright-0" />
		</div>
		<div class="clear"></div>
	</div>
</div>
	
<?php 
	echo $this->Form->end();
?>