set :application,  "wix-adsense"
set :domain,       "wix.codeoasis.com"
set :user,            "deploy"
set :deploy_to,    "/var/www/wix-adsense"
set :app_path,     "app"
set :web_path,     "web"
set :symfony_env_prod, "stg"

# set permissions
set :writable_dirs,       ["app/cache", "app/logs"]
set :webserver_user,      "www-data"
set :permission_method,   :acl
set :use_set_permissions, true
set :use_sudo, false

set :shared_files,    ["app/config/parameters.yml"]
set :shared_children, [app_path + "/logs"]

set :repository,  "git@git.codeoasis.com:wix/wix-adsense.git"
set :scm,         :git
set :deploy_via,  :remote_cache

# The following line tells Capifony to deploy the last Git tag.
# Since Jenkins creates and pushes a tag following a successful build this should always be the last tested version of the code.
set :branch, "stage"

#Need this to false so it won't remove app_stg and app_dev
set :clear_controllers,     false

#Dump Assetic
set :dump_assetic_assets,   true

set :model_manager, "doctrine"

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain, :primary => true       # This may be the same as your `Web` server

set :use_composer,    true
set :copy_vendors, true
set :composer_options,      "--no-dev --verbose --prefer-dist --optimize-autoloader --no-progress"

set  :keep_releases,  5

# Confirmations will not be requested from the command line.
set :interactive_mode, false

task :restart_php do
  run "cd #{release_path}; service php-fpm reload"

  capifony_puts_ok
end

# Run compass
task :compass_compile do
    run "cd #{release_path}/src/Wix/GoogleAdsenseBundle/Resources/public; compass compile"
    run "cd #{release_path}; php app/console cache:clear --env=stg"
    run "cd #{release_path}; php app/console assets:install --symlink web/"
    run "cd #{release_path}; php app/console assetic:dump --env=stg"

    capifony_puts_ok
end


#after "provide_permissions", "restart_php"
after "deploy", "compass_compile"

# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL
