import configparser

class Config(object):
    def __init__(self, config_file):
        config = configparser.ConfigParser()
        config.read(config_file)

        self.key = config.get('Keys', 'PrivateKey', fallback=None)

        if not self.key:
            raise ValueError('A value for \'key\' was not specified in the configuration file.')
