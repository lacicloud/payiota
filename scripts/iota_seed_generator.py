import random, string
print ''.join(random.SystemRandom().choice(string.ascii_uppercase + "9") for _ in range(81))