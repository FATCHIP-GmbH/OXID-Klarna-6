<div class="col-sm-6">
    <div class="drop-container" id="klarnaLoginWidget">
        <div class="drop-trigger">
            <div class="klarna-label">
                <span class="glyphicon glyphicon-user pull-left" aria-hidden="true"></span>
                <span class="klarna-login-label"> [{oxmultilang ident="TCKLARNA_ALREADY_A_CUSTOMER"}]</span>
                <span class="glyphicon glyphicon-menu-down pull-right" aria-hidden="true"></span>
            </div>
        </div>
        <div class="drop-content">
            [{assign var="bIsError" value=0}]
            [{capture name="loginErrors"}]
                [{foreach from=$Errors.loginBoxErrors item=oEr key=key}]
                    <p id="errorBadLogin" class="alert alert-danger">[{$oEr->getOxMessage()}]</p>
                    [{assign var="bIsError" value=1}]
                [{/foreach}]
            [{/capture}]
            <form class="form" name="login" action="[{$oViewConf->getSslSelfLink()}]"
                  method="post">
                <div id="loginBox" class="" [{if $bIsError}]style="display: block;" [{/if}]>
                    [{$oViewConf->getHiddenSid()}]
                    [{$oViewConf->getNavFormParams()}]
                    <input type="hidden" name="fnc" value="login_noredirect">
                    <input type="hidden" name="cl" value="[{$oViewConf->getTopActiveClassName()}]">
                    <input type="hidden" name="pgNr" value="[{$oView->getActPage()}]">
                    <input type="hidden" name="CustomError" value="loginBoxErrors">

                    <div class="form-group">
                        <input type="email" name="lgn_usr" value=""
                               class="form-control"
                               placeholder="[{oxmultilang ident="EMAIL_ADDRESS"}]">
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <input type="password" name="lgn_pwd"
                                   class="form-control"
                                   value="" placeholder="[{oxmultilang ident="PASSWORD"}]">
                            <span class="input-group-btn">
                                        <a class="forgotPasswordOpener btn btn-default"
                                           href="[{oxgetseourl ident=$oViewConf->getSslSelfLink()|cat:"cl=forgotpwd"}]"
                                           title="[{oxmultilang ident="FORGOT_PASSWORD"}]">?</a>
                                </span>
                        </div>
                    </div>

                    [{if $oViewConf->isFunctionalityEnabled( "blShowRememberMe" )}]
                        <div class="checkbox checkbox-fix">
                            <label>
                                <input type="checkbox" class="checkbox-fix" value="1" name="lgn_cook">
                                [{oxmultilang ident="REMEMBER_ME"}]
                            </label>
                        </div>
                    [{/if}]

                    <button type="submit" class="btn btn-primary">
                        [{oxmultilang ident="LOGIN"}]
                    </button>

                </div>
            </form>
        </div>
    </div>

</div>
