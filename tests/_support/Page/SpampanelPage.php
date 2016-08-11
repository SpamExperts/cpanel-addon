<?php

namespace Page;

class SpampanelPage
{
    const LOGOUT_LINK = '//a[@href="#confirmLogoutDialog"]';
    const LOGOUT_CONFIRM_LINK = '//a[@href="/index.php?action=logout"]';

    const EDIT_ROUTE_BUTTON = "//a[@href='/domainroute.php'][1]";

    const ROUTE_FIELD_XPATH = "//*[@id='route_host_new']";
    const ROUTE_FIELD_CSS = "#route_host_new";

    const PORT_FIELD_XPATH = "//*[@id='route_port_new']";
    const PORT_FIELD_CSS = "#route_port_new";

}
