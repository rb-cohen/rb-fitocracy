<style>
    .rb-fitocracy-username{
        font-size: 1.2em;
    }
    .rb-fitocracy-level{
        float: right;
    }
    .rb-fitocracy-level span{
        font-size: 1.1em;
        font-weight: bold;
    }
    .rb-fitocracy-bar-container{
        clear: both;
        width: 100%;
        margin: 5px 0;
    }

    .rb-fitocracy-bar-long{
        width: 100%;
        height: 10px;
        background-color: #e5e5e5;
        -moz-border-radius: 15px;
        border-radius: 15px;
    }
    .rb-fitocracy-bar-progress{
        width: 50%;
        height: 100%;
        background-color: #60dc3d;
        -moz-border-radius: 15px;
        border-radius: 15px;
    }
</style>

<div class="rb-fitocracy-widget">
    <a href="https://www.fitocracy.com/profile/<?php echo $user->username; ?>" class="rb-fitocracy-username" target="_blank"><?php echo $user->username; ?></a>
    <span class="rb-fitocracy-level">Level <span><?php echo $user->level; ?></span></span>

    <div class="rb-fitocracy-bar-container">
        <div class="rb-fitocracy-bar-long">
            <div class="rb-fitocracy-bar-progress" style="width: <?php echo $progressPercent; ?>%" title="<?php echo $user->points; ?>/<?php echo $user->points_levelup; ?> points"></div>
        </div>
    </div>
</div>