class Dog:
    """狗"""
    cat = "大型犬"

    def __init__(self, name):
        self.name = name


    def reSetName(self, newName):
        """reset the name"""
        self.name = newName

    def getTheName(self):
        return self.name
    

newDog = Dog("PPN")
newDog.reSetName("YangXue")
print(newDog.getTheName())