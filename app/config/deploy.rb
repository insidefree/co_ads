set :stages,        %w(production staging)
set :default_stage, "staging"
set :stage_dir,     "app/config"
require 'capistrano/ext/multistage'
