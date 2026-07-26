# ============================================
# Python 类与对象学习
# 知识点：类定义、__init__、方法、继承
# ============================================

# --------------------------------------------
# 1. 类与对象基础
# --------------------------------------------
# 类是对象的蓝图/模板，对象是类的实例

class Dog:
    """一个简单的狗类"""
    # 类属性 —— 所有实例共享
    species = "犬科"

    # __init__ 是构造方法，创建对象时自动调用
    # self 代表实例本身，类似其他语言的 this
    def __init__(self, name, age):
        # 实例属性 —— 每个对象独有
        self.name = name
        self.age = age
        print(f"一只名叫 {self.name} 的狗被创建了！")

    # 实例方法 —— 第一个参数必须是 self
    def bark(self):
        print(f"{self.name}: 汪汪！")

    def info(self):
        print(f"名字: {self.name}, 年龄: {self.age}, 物种: {self.species}")


# 创建对象（实例化）
dog1 = Dog("旺财", 3)   # 输出: 一只名叫 旺财 的狗被创建了！
dog2 = Dog("来福", 1)   # 输出: 一只名叫 来福 的狗被创建了！

# 调用方法
dog1.bark()             # 输出: 旺财: 汪汪！
dog1.info()             # 输出: 名字: 旺财, 年龄: 3, 物种: 犬科
dog2.info()             # 输出: 名字: 来福, 年龄: 1, 物种: 犬科

# 类属性 vs 实例属性
print(f"\ndog1 的名字(实例属性): {dog1.name}")
print(f"Dog 的物种(类属性):   {Dog.species}")
# 修改类属性会影响所有实例
Dog.species = "哺乳动物"
print(f"修改后 dog1 物种: {dog1.species}")  # 哺乳动物
print(f"修改后 dog2 物种: {dog2.species}")  # 哺乳动物
Dog.species = "犬科"  # 改回来


# --------------------------------------------
# 2. __init__ 详解
# --------------------------------------------
# __init__ 不是"构造函数"，而是"初始化方法"
# 真正创建对象的是 __new__，__init__ 负责初始化属性

class Person:
    def __init__(self, name, age=18):  # 参数可以有默认值
        self.name = name
        self.age = age
        self.friends = []  # 可以初始化为空列表等

    def add_friend(self, friend_name):
        self.friends.append(friend_name)
        print(f"{friend_name} 成为了 {self.name} 的朋友")

    def show_friends(self):
        print(f"{self.name} 的朋友: {', '.join(self.friends) if self.friends else '暂无'}")


p = Person("小明", 20)
p.add_friend("小红")
p.add_friend("小刚")
p.show_friends()  # 输出: 小明 的朋友: 小红, 小刚

p2 = Person("小华")  # 使用默认年龄 18
print(f"\n{p2.name} 的年龄: {p2.age}")  # 输出: 18


# --------------------------------------------
# 3. 方法的种类
# --------------------------------------------

class Calculator:
    brand = "Python牌"

    # 实例方法 —— 访问实例属性，第一个参数是 self
    def __init__(self, owner):
        self.owner = owner
        self.history = []

    def add(self, a, b):
        result = a + b
        self.history.append(f"{a} + {b} = {result}")
        return result

    def show_history(self):
        if not self.history:
            print(f"{self.owner} 的计算器: 暂无记录")
        else:
            print(f"{self.owner} 的计算器记录:")
            for record in self.history:
                print(f"  {record}")

    # 类方法 —— 用 @classmethod 装饰器，第一个参数是 cls（类本身）
    @classmethod
    def get_brand(cls):
        return cls.brand

    @classmethod
    def set_brand(cls, new_brand):
        cls.brand = new_brand
        print(f"品牌已更新为: {cls.brand}")

    # 静态方法 —— 用 @staticmethod 装饰器，不需要 self 也不需要 cls
    # 就像普通函数，只是放在类的命名空间里
    @staticmethod
    def is_positive(n):
        return n > 0


calc = Calculator("小明")
print(f"\n{calc.add(3, 5)}")   # 输出: 8
print(f"{calc.add(10, 20)}")  # 输出: 30
calc.show_history()

# 类方法调用
print(f"\n品牌: {Calculator.get_brand()}")
Calculator.set_brand("超级计算器")
print(f"品牌: {Calculator.get_brand()}")
Calculator.set_brand("Python牌")  # 改回来

# 静态方法调用
print(f"\n5 是正数吗? {Calculator.is_positive(5)}")   # True
print(f"-3 是正数吗? {Calculator.is_positive(-3)}")  # False


# --------------------------------------------
# 4. 继承
# --------------------------------------------
# 子类继承父类的属性和方法，可以扩展或重写

# 父类
class Animal:
    def __init__(self, name, sound):
        self.name = name
        self.sound = sound

    def speak(self):
        print(f"{self.name}: {self.sound}！")

    def info(self):
        print(f"我是 {self.name}")


# 子类 —— 继承 Animal
class Cat(Animal):
    def __init__(self, name, color):
        # super() 调用父类的 __init__
        super().__init__(name, "喵")
        self.color = color

    # 新增方法
    def purr(self):
        print(f"{self.name} 在呼噜呼噜~")

    # 重写父类方法（方法覆盖/Override）
    def info(self):
        # 也可以用 super() 调用父类版本
        super().info()
        print(f"毛色: {self.color}")


# 另一个子类
class Duck(Animal):
    def __init__(self, name):
        super().__init__(name, "嘎嘎")

    # 重写 speak，加上特有行为
    def speak(self):
        super().speak()
        print(f"  ({self.name} 摇了摇尾巴)")


cat = Cat("橘座", "橘色")
cat.speak()   # 输出: 橘座: 喵！
cat.purr()    # 输出: 橘座 在呼噜呼噜~
cat.info()    # 输出: 我是 橘座 / 毛色: 橘色

duck = Duck("唐老鸭")
duck.speak()  # 输出: 唐老鸭: 嘎嘎！ / (唐老鸭 摇了摇尾巴)
duck.info()   # 输出: 我是 唐老鸭

# isinstance 检查继承关系
print(f"\n橘座 是 Cat 吗?   {isinstance(cat, Cat)}")     # True
print(f"橘座 是 Animal 吗? {isinstance(cat, Animal)}")   # True
print(f"橘座 是 Duck 吗?  {isinstance(cat, Duck)}")     # False


# --------------------------------------------
# 5. 多重继承（了解即可）
# --------------------------------------------

class Flyable:
    def fly(self):
        print(f"{self.name} 在飞！")

class Swimmable:
    def swim(self):
        print(f"{self.name} 在游泳！")

# 同时继承两个类
class Duck2(Animal, Flyable, Swimmable):
    def __init__(self, name):
        super().__init__(name, "嘎嘎")

d2 = Duck2("野鸭")
d2.speak()  # 来自 Animal
d2.fly()    # 来自 Flyable
d2.swim()   # 来自 Swimmable


# --------------------------------------------
# 6. 综合练习：模拟一个简单的游戏角色系统
# --------------------------------------------
print("\n" + "=" * 40)
print("综合练习：游戏角色系统")
print("=" * 40)

class Character:
    """角色基类"""
    def __init__(self, name, hp=100, attack=10):
        self.name = name              # 公有属性：名字可以随意访问
        self._hp = hp                 # 约定私有：外部不建议直接修改
        self.__attack_power = attack  # 名称改写私有：外部无法直接访问
        self.is_alive = True

    # getter —— 读取私有属性
    @property
    def hp(self):
        return self._hp

    @hp.setter
    def hp(self, value):
        # 在 setter 里可以加校验逻辑
        if value < 0:
            self._hp = 0
        else:
            self._hp = value
        self.is_alive = self._hp > 0

    @property
    def attack_power(self):
        return self.__attack_power

    @attack_power.setter
    def attack_power(self, value):
        if value < 0:
            print("攻击力不能为负数！")
            return
        self.__attack_power = value

    def attack(self, target):
        if not self.is_alive:
            print(f"{self.name} 已经倒下，无法攻击！")
            return
        if not target.is_alive:
            print(f"{target.name} 已经倒下了！")
            return
        target.take_damage(self.attack_power)
        print(f"{self.name} 攻击了 {target.name}，造成 {self.attack_power} 点伤害！")

    def take_damage(self, damage):
        self.hp -= damage
        if self.hp <= 0:
            self.hp = 0
            self.is_alive = False
            print(f"  {self.name} 倒下了！")
        else:
            print(f"  {target.name} 剩余 HP: {self.hp}")  # 这里有个 bug，你能找到吗？

    def show_status(self):
        status = "存活" if self.is_alive else "倒下"
        print(f"[{self.name}] HP: {self.hp} | 攻击力: {self.attack_power} | 状态: {status}")


class Warrior(Character):
    """战士 —— 高血量"""
    def __init__(self, name):
        super().__init__(name, hp=150, attack=15)

    # 特有技能
    def heavy_strike(self, target):
        damage = self.attack_power * 2
        target.take_damage(damage)
        print(f"{self.name} 对 {target.name} 使用了重击！造成 {damage} 点伤害！")


class Mage(Character):
    """法师 —— 高攻击"""
    def __init__(self, name):
        super().__init__(name, hp=80, attack=25)

    # 特有技能
    def fireball(self, target):
        damage = self.attack_power * 3
        target.take_damage(damage)
        print(f"{self.name} 对 {target.name} 释放了火球术！造成 {damage} 点伤害！")


# 战斗演示
warrior = Warrior("亚瑟")
mage = Mage("梅林")

warrior.show_status()
mage.show_status()

print("\n--- 战斗开始 ---")
warrior.attack(mage)
mage.fireball(warrior)
warrior.heavy_strike(mage)

print("\n--- 战斗结束 ---")
warrior.show_status()
mage.show_status()