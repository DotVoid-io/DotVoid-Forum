<template>
    <div id="app" class="dark">
        <header>
            <img id="logo" src="../img/logo_dark.svg" />

            <form @submit.prevent="search($event)">
                <input id="search-bar" placeholder="Rechercher..." /> <!-- TODO: Search icon -->
            </form>

            <div id="logged-user">
                <img id="avatar" src="../img/avatar_test.png" />
                <!-- TODO: Notifications -->
            </div>
        </header>

        <div id="content">
            <router-view/>
        </div>
    </div>
</template>

<style lang="scss">
    @import "../sass/variables";


    $header-height: 78px;
    $logo-padding: 15px;
    $search-bar-padding: 17px;
    $avatar-padding: 12px;


    html, body, #app {
        margin: 0;
        padding: 0;

        width: 100vw;
        min-height: calc(100vh - 30px); /* TODO: FOR DEBUG BAR */

        overflow-x: hidden;
    }

    #app {
        &.dark {
            background: get_color_dark(background);
        }

        &.light {
            background: get_color_light(background);
        }
    }

    #app, input {
        font-family: 'Source Sans Pro', 'Arial', sans-serif;
    }

    header {
        height: $header-height;
        width: 100%;

        @include themed(background, header);
        box-shadow: 0 1px 9px #0a0a0a;

        * {
            float: left;
        }

        #logo {
            height: $header-height - $logo-padding * 2;

            margin-top: $logo-padding;
            margin-left: 60px;
            margin-right: 80px;
        }

        form {
            display: inline-block;

            #search-bar {
                height: $header-height - $search-bar-padding * 2;
                width: calc(100vw - 475px); /* flexboxes ? */
                max-width: 600px;

                margin-top: $search-bar-padding;
                margin-left: 50px;

                padding-left: 12px;

                border: none;
                border-radius: 3px;

                font-size: 25px;

                &::placeholder {
                    font-style: italic;
                    @include themed(color, placeholder);
                }
            }
        }

        #logged-user {
            display: inline-block;
            float: right;

            #avatar {
                $avatar-size: $header-height - $avatar-padding * 2;

                width: $avatar-size;
                height: $avatar-size;

                margin-top: $avatar-padding;
                margin-right: 20px;

                border-radius: $avatar-size;
            }
        }
    }

    #content {
        width: 100%;
        height: calc(100% - #{$header-height});

        @include themed(color, text);
    }
</style>
