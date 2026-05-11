<?php

function do_toggle_follow()
{
mysqli_safe_query(
    "UPDATE app_state SET follow_mode = NOT follow_mode WHERE id = 1",
    __FILE__, __LINE__
);
notify_ws();
}
