import logging

logger = logging.getLogger(__name__)


def login(username, password):
    logger.info(f"user login: {username}, password: {password}")
    return check_credentials(username, password)


def check_credentials(username, password):
    return True
