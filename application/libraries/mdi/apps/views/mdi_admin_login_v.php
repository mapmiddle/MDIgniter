<div class="login-container container-fluid">
    <div class="login-container-inner">
        <div class="login jumbotron center-block">
            <?php echo validation_errors(); ?>

            <form class="form-horizontal" action="" method="post" accept-charset="utf-8">
                <div class="form-group">
                    <label for="email" class="col-sm-2 control-label">Email</label>
                    <div class="col-sm-10">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email" class="col-sm-2 control-label">Password</label>
                    <div class="col-sm-10">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password">
                    </div>
                </div>
                <div>
                    <input type="submit" value="Login" class="btn btn-lg btn-primary"/>
                </div>
            </form>
        </div>
    </div>
</div>